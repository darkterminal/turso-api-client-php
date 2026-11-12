<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Integrations;

use Darkterminal\TursoPlatformAPI\Exceptions\HttpException;
use Darkterminal\TursoPlatformAPI\Exceptions\TransportException;
use Darkterminal\TursoPlatformAPI\Exceptions\UnexpectedResponseException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Nyholm\Psr7\Request;

/**
 * Region resolver client.
 *
 * Endpoint contract (verified against live service on 2026-11-12):
 *   GET https://region.turso.io/  (POST is also accepted)
 *   no auth; server-selected region, CORS-safe
 *   response: {"server": "aws-us-west-2", "client": "lax"}
 *
 * The `server` field is the AWS region code for the resolver endpoint itself;
 * `client` is the airport code of the caller. This is a read-only
 * integration with a single method and no credentials — isolated from the
 * Platform API client by design.
 */
final class RegionResolver
{
    private const DEFAULT_BASE_URL = 'https://region.turso.io';

    public function __construct(
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
        private readonly int $timeout = 5,
        private readonly ?ClientInterface $transport = null,
    ) {}

    /**
     * Ask the resolver for the closest region.
     *
     * @return array{server: string, client: string}
     */
    public function closest(): array
    {
        $base = rtrim($this->baseUrl, '/');
        $request = new Request('GET', $base . '/');

        try {
            $response = $this->transport()->sendRequest($request);
        } catch (NetworkExceptionInterface $e) {
            throw new TransportException('Region resolver request failed at the transport layer.');
        }

        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();

        if ($status !== 200) {
            throw new HttpException(
                'Region resolver returned non-200 status.',
                $status,
                $raw,
                null,
            );
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['server']) || !isset($decoded['client'])) {
            throw new UnexpectedResponseException('Region resolver response is missing required fields (server/client).');
        }

        return [
            'server' => (string) $decoded['server'],
            'client' => (string) $decoded['client'],
        ];
    }

    private function transport(): ClientInterface
    {
        return $this->transport ?? new \Darkterminal\TursoPlatformAPI\Transport\CurlTransport($this->timeout);
    }
}
