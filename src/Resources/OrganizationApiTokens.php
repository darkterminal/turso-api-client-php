<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Resources;

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;

/**
 * Organization API tokens resource — operationIds:
 * listOrganizationAPITokens, revokeOrganizationAPIToken.
 *
 * Envelopes: list -> {tokens: [...]} | revoke -> {token: ...}
 */
final class OrganizationApiTokens
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * List API tokens for an organization.
     * @return array<mixed>
     */
    public function list(): array
    {
        $decoded = $this->client->request('GET', $this->orgPath('/api-tokens'));
        return $decoded['tokens'] ?? $decoded;
    }

    /**
     * Revoke (delete) an organization API token by ID.
     * @return array<mixed>
     */
    public function revoke(string $tokenId): array
    {
        if (trim($tokenId) === '') {
            throw new ValidationException('Token ID must not be empty.');
        }
        if (preg_match('/[\\/]/', $tokenId) === 1) {
            throw new ValidationException('Token ID must not contain path separators.');
        }
        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('DELETE', $this->orgPath('/api-tokens/' . rawurlencode($tokenId)));
        return $decoded;
    }

    private function orgPath(string $segment = ''): string
    {
        $org = $this->client->organization();
        $base = '/v1/organizations/' . rawurlencode($org);
        return $segment === '' ? $base : $base . $segment;
    }
}
