<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Integrations;

use Darkterminal\TursoPlatformAPI\Exceptions\AuthenticationException;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;
use Darkterminal\TursoPlatformAPI\Exceptions\TransportException;
use Darkterminal\TursoPlatformAPI\Exceptions\ServerException;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;

/**
 * Dedicated streaming database uploader.
 *
 * Implements the POST {hostname}/v1/upload contract (verified live on
 * 2026-11-12 against Turso Cloud — see CHANGELOG for the confirmed
 * success response body):
 *   - raw binary SQLite body, Content-Length required
 *   - auth: database-scoped token (NOT the Platform API token)
 *   - target database must be created with seed.type = 'database_upload'
 *   - file prerequisites: journal_mode=WAL, page_size=4096, auto_vacuum=0, encoding=UTF-8
 *   - optional x-turso-encryption-key / x-turso-encryption-cipher for encrypted DBs
 *
 * Credentials are NEVER attached to exception messages or logs.
 *
 * ADR-009: separate from the Platform client — upload never reuses the
 * Platform API token and never follows redirects off the database host.
 *
 * Pass a PSR-18 ClientInterface (e.g. in tests) to inject a test double;
 * otherwise the streaming CURL path is used (file is NOT loaded entirely
 * into memory).
 */
final class DatabaseUploader
{
    private readonly string $hostname;
    private readonly string $databaseToken;
    private readonly int $timeout;
    private readonly bool $verifyTls;
    private readonly ?ClientInterface $transport;

    /**
     * @param string $hostname         database hostname, e.g. 'dmpupload-…-….turso.io'
     * @param string $databaseToken    database-scoped JWT
     * @param int $timeout             seconds (default 120; large file uploads can be slow)
     * @param ClientInterface|null $transport  optional PSR-18 transport (for tests)
     * @param bool $verifyTls          reject self-signed/internal hosts when false
     */
    public function __construct(
        string $hostname,
        string $databaseToken,
        int $timeout = 120,
        ?ClientInterface $transport = null,
        bool $verifyTls = true,
    ) {
        if (preg_match('{^http://}i', trim($hostname))) {
            throw new ValidationException('Upload hostname must use https, not http, with only https URLs allowed.');
        }
        // accept both 'host' and 'https://host' — normalize to bare host
        $host = preg_replace('{^https?://}i', '', trim($hostname));
        $host = parse_url('https://' . $host, PHP_URL_HOST);
        if (!is_string($host) || $host === '' || !preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9.-]*[A-Za-z0-9])?$/', $host)) {
            throw new ValidationException('Upload hostname must be a valid https host (e.g. "dmpupload-….turso.io").');
        }
        $this->hostname = $host;

        if (trim($databaseToken) === '') {
            throw new ValidationException('Database token must not be empty.');
        }
        if ($timeout < 1) {
            throw new ValidationException('Timeout must be at least 1 second.');
        }

        $this->databaseToken = $databaseToken;
        $this->timeout = $timeout;
        $this->transport = $transport;
        $this->verifyTls = $verifyTls;
    }

    /**
     * Upload a SQLite database dump to a database created with seed.type = 'database_upload'.
     *
     * Pre-flight: file must exist, be readable, and match the documented SQLite
     * PRAGMAs (WAL / page_size=4096 / auto_vacuum=0 / UTF-8). The file is
     * streamed — the SDK never holds the whole file in memory.
     *
     * @param array{key?: string, cipher?: string}|null $encryption  encryption for
     *        encrypted databases; leave null for plain uploads
     * @return string                                                    raw response body
     */
    public function uploadDump(string $filePath, ?array $encryption = null): string
    {
        $size = $this->preflightAndGetSize($filePath);

        if ($this->transport !== null) {
            return $this->uploadViaPsr18($filePath, $size, $encryption);
        }

        return $this->uploadViaCurlStream($filePath, $size, $encryption);
    }

    private function preflightAndGetSize(string $filePath): int
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new ValidationException('Database file not readable: ' . $filePath);
        }

        $size = (int) filesize($filePath);
        if ($size <= 0) {
            throw new ValidationException('Database file is empty: ' . $filePath);
        }

        try {
            $db = new \PDO('sqlite:' . $filePath, null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            $journal  = (string) $db->query('PRAGMA journal_mode')->fetchColumn();
            $page     = (int)    $db->query('PRAGMA page_size')->fetchColumn();
            $vacuum   = (int)    $db->query('PRAGMA auto_vacuum')->fetchColumn();
            $encoding = (string) $db->query('PRAGMA encoding')->fetchColumn();
            $db = null;
        } catch (\Throwable $e) {
            throw new ValidationException('Pre-flight check failed: file does not look like a valid SQLite database.');
        }

        if (strtolower($journal) !== 'wal') {
            throw new ValidationException('SQLite database must be in WAL journal_mode (got: ' . $journal . ').');
        }
        if ($page !== 4096) {
            throw new ValidationException('SQLite page_size must be 4096 (got: ' . $page . ').');
        }
        if ($vacuum !== 0) {
            throw new ValidationException('SQLite auto_vacuum must be 0 (got: ' . $vacuum . ').');
        }
        if (stripos($encoding, 'UTF') === false) {
            throw new ValidationException('SQLite encoding must be UTF-8 (got: ' . $encoding . ').');
        }

        return $size;
    }

    /**
     * @param array{key?: string, cipher?: string}|null $encryption
     * @return array<string, string>
     */
    private function commonHeaders(int $size, ?array $encryption): array
    {
        $map = [
            'Authorization' => 'Bearer ' . $this->databaseToken,
            'Content-Length' => (string) $size,
            'Content-Type' => 'application/octet-stream',
        ];
        if ($encryption !== null) {
            if (isset($encryption['key']) && $encryption['key'] !== '') {
                $map['x-turso-encryption-key'] = $encryption['key'];
            }
            if (isset($encryption['cipher']) && $encryption['cipher'] !== '') {
                $map['x-turso-encryption-cipher'] = $encryption['cipher'];
            }
        }
        return $map;
    }

    /**
     * Render associative headers into "Name: Value" lines for the curl path.
     *
     * @param array<string, string> $map
     * @return list<string>
     */
    private function renderHeaders(array $map): array
    {
        $lines = [];
        foreach ($map as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }
        return $lines;
    }

    /** @param array{key?: string, cipher?: string}|null $encryption */
    private function uploadViaPsr18(string $filePath, int $size, ?array $encryption): string
    {
        $stream = \Nyholm\Psr7\Stream::create(fopen($filePath, 'rb'));

        $request = new Request(
            'POST',
            'https://' . $this->hostname . '/v1/upload',
            $this->commonHeaders($size, $encryption),
            $stream,
        );

        try {
            $response = $this->transport->sendRequest($request);
        } catch (NetworkExceptionInterface $e) {
            throw new TransportException('Upload request failed at the transport layer.');
        }

        return $this->guardResponse($response);
    }

    /** @param array{key?: string, cipher?: string}|null $encryption */
    private function uploadViaCurlStream(string $filePath, int $size, ?array $encryption): string
    {
        $headers = $this->renderHeaders($this->commonHeaders($size, $encryption));

        $ch = curl_init('https://' . $this->hostname . '/v1/upload');
        $fh = fopen($filePath, 'rb');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => $this->verifyTls,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_UPLOAD => true,
            CURLOPT_INFILE => $fh,
            CURLOPT_INFILESIZE => $size,
        ]);

        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $body = is_string($body) ? $body : '';

        if ($err !== '') {
            throw new TransportException('Upload request failed at the transport layer.');
        }

        return $this->guardStatusAndBody($code, $body);
    }

    private function guardResponse(?\Psr\Http\Message\ResponseInterface $response): string
    {
        return $this->guardStatusAndBody($response->getStatusCode(), (string) $response->getBody());
    }

    private function guardStatusAndBody(int $code, string $body): string
    {
        if ($code >= 200 && $code < 300) {
            return $body;
        }
        if ($code === 401) {
            throw new AuthenticationException(
                'Upload unauthorized (database token rejected or not valid for this database).',
                $code,
                $body,
            );
        }
        if ($code === 400) {
            throw new ValidationException(
                'Upload rejected by server (invalid file format or database not in upload mode).',
            );
        }
        throw new ServerException('Upload failed.', $code, $body);
    }
}
