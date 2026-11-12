<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Client;

use Darkterminal\TursoPlatformAPI\Exceptions\AuthenticationException;
use Darkterminal\TursoPlatformAPI\Exceptions\HttpException;
use Darkterminal\TursoPlatformAPI\Exceptions\NotFoundException;
use Darkterminal\TursoPlatformAPI\Exceptions\RateLimitException;
use Darkterminal\TursoPlatformAPI\Exceptions\ServerException;
use Darkterminal\TursoPlatformAPI\Exceptions\TransportException;
use Darkterminal\TursoPlatformAPI\Exceptions\UnexpectedResponseException;
use Darkterminal\TursoPlatformAPI\Resources\Users;
use Darkterminal\TursoPlatformAPI\Transport\CurlTransport;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;

/**
 * Turso Platform client.
 *
 * Construct via Client::builder() (preferred) or ClientBuilder directly:
 *
 *     $client = Client::builder()->withToken($token)->build();
 *     $me = $client->user()->get();
 *
 * Credentials are never attached to exception messages, bodies or logs.
 */
final class Client
{
    public function __construct(
        private readonly Configuration $configuration,
    ) {}

    public static function builder(): ClientBuilder
    {
        return new ClientBuilder();
    }

    public static function version(): string
    {
        return '2.0.0-dev';
    }

    public function configuration(): Configuration
    {
        return $this->configuration;
    }

    public function user(): Users
    {
        return new Users($this);
    }

    /**
     * Execute a Platform API request and return the decoded JSON payload as an array.
     *
     * @param array<string, scalar|array> $query
     * @param array<int|string, mixed>|object|null $body
     * @return array<mixed>
     */
    public function request(string $method, string $path, array $query = [], array|object|null $body = null): array
    {
        $base = rtrim($this->configuration->baseUrl, '/');
        $url = $base . '/' . ltrim($path, '/');
        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $payload = $body !== null ? (string) json_encode($body) : null;
        $request = new Request(
            method: strtoupper($method),
            uri: $url,
            headers: [
                'Authorization' => 'Bearer ' . $this->configuration->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'turso-platform-php/' . self::version(),
            ],
            body: $payload ?? '',
        );

        try {
            $response = $this->transport()->sendRequest($request);
        } catch (NetworkExceptionInterface $e) {
            throw new TransportException('Platform API request failed at the transport layer.');
        }

        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();
        $decoded = json_decode($raw, true);

        if ($status >= 200 && $status < 300) {
            if (!is_array($decoded)) {
                throw new UnexpectedResponseException('Expected a JSON object/array in the response body.');
            }
            return $decoded;
        }

        $message = is_array($decoded) && isset($decoded['error']) && is_string($decoded['error'])
            ? $decoded['error']
            : 'Platform API error';
        $errorCode = is_array($decoded) && isset($decoded['errorCode']) && is_string($decoded['errorCode'])
            ? $decoded['errorCode']
            : null;

        throw match (true) {
            $status === 401, $status === 403 => new AuthenticationException($message, $status, $raw, $errorCode),
            $status === 404 => new NotFoundException($message, $status, $raw, $errorCode),
            $status === 429 => new RateLimitException($message, $this->retryAfter($response->getHeaderLine('Retry-After')), $raw, $errorCode),
            $status >= 500 => new ServerException($message, $status, $raw, $errorCode),
            default => new HttpException($message, $status, $raw, $errorCode),
        };
    }

    private function transport(): ClientInterface
    {
        return $this->configuration->transport ?? new CurlTransport($this->configuration->timeout);
    }

    private function retryAfter(string $header): ?int
    {
        $value = trim($header);
        return ctype_digit($value) ? (int) $value : null;
    }
}
