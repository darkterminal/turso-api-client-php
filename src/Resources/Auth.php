<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Resources;

use Darkterminal\TursoPlatformAPI\Client\Client;

/**
 * Auth resource — operationId: validateAPIToken.
 *
 * Envelope: {exp: int}
 */
final class Auth
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /** Validate the configured API token. */
    public function validate(): array
    {
        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('GET', '/v1/auth/validate');
        return $decoded;
    }
}
