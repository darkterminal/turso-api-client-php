<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Resources;

use Darkterminal\TursoPlatformAPI\Client\Client;

/**
 * Current-user resource — GET /v1/user (operationId: getCurrentUser).
 * Contract pinned in openapi/snapshots/ and mapped in openapi/coverage-manifest.yaml.
 */
final class Users
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Return the authenticated user's profile as an associative array.
     *
     * @return array<mixed>
     */
    public function get(): array
    {
        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('GET', '/v1/user');
        return $decoded;
    }
}
