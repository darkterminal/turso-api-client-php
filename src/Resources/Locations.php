<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Resources;

use Darkterminal\TursoPlatformAPI\Client\Client;

/**
 * Locations resource — operationId: listLocations.
 *
 * Envelope: {locations: [...]}
 */
final class Locations
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /** List available database locations/regions. */
    public function list(): array
    {
        $decoded = $this->client->request('GET', '/v1/locations');
        return $decoded['locations'] ?? $decoded;
    }
}
