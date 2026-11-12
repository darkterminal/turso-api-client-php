<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Transport;

use Darkterminal\TursoPlatformAPI\Exceptions\TransportException;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Minimal PSR-18 transport on ext-curl.
 *
 * - No redirects are followed automatically (CURLOPT_FOLLOWLOCATION = false),
 *   matching PSR-18 clients that treat redirect policy as transport-specific.
 * - Network-level failures throw TransportException (never include secrets).
 * - Response headers/status/body are captured via CURLOPT_HEADERFUNCTION and
 *   CURLOPT_WRITEFUNCTION for full PSR-7 fidelity.
 */
final class CurlTransport implements ClientInterface
{
    public function __construct(
        private readonly int $timeout = 20,
        private readonly bool $verifyTls = true,
    ) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $ch = curl_init();

        $responseBody = '';
        $responseHeaders = [];
        $statusLine = '';

        curl_setopt_array($ch, [
            CURLOPT_URL => (string) $request->getUri(),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => $this->verifyTls,
            CURLOPT_HTTPHEADER => $this->headersToArray($request->getHeaders()),
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$statusLine, &$responseHeaders): int {
                $len = strlen($line);
                if (str_starts_with($line, 'HTTP/')) {
                    $statusLine = $line;
                    return $len;
                }
                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $responseHeaders[trim($name)][] = trim($value);
                }
                return $len;
            },
            CURLOPT_WRITEFUNCTION => static function ($ch, string $data) use (&$responseBody): int {
                $responseBody .= $data;
                return strlen($data);
            },
        ]);

        $payload = (string) $request->getBody();
        if ($payload !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $ok = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if ($ok === false || $err !== '') {
            throw new TransportException('Platform API request failed at the transport layer.');
        }

        return new Response($status, $responseHeaders, $responseBody);
    }

    /** @param array<string, list<string>> $headers */
    private function headersToArray(array $headers): array
    {
        $flat = [];
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $flat[] = $name . ': ' . $value;
            }
        }
        return $flat;
    }
}
