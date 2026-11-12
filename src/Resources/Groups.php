<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Resources;

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Enums\Authorization;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;

/**
 * Groups resource — 13 operations pinned in openapi/coverage-manifest.yaml
 * (operationIds: listGroups, createGroup, getGroup, deleteGroup,
 * getGroupConfiguration, updateGroupConfiguration, transferGroup,
 * unarchiveGroup, addLocationToGroup, removeLocationFromGroup,
 * updateGroupDatabases, createGroupToken, invalidateGroupTokens).
 *
 * Responses follow the contract envelope:
 *   list -> {groups: [...]} | create/get/delete/unarchive/locations -> {group: {...}}
 *   transfer -> bare Group object | configuration -> {delete_protection: bool}
 *   createToken -> {jwt: '...'} | rotate/update -> 200 No Content
 */
final class Groups
{
    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = [
        'vector', 'crypto', 'fuzzy', 'math', 'stats', 'text', 'unicode', 'uuid', 'regexp', 'vec',
    ];

    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * @return array<mixed>
     */
    public function list(): array
    {
        $decoded = $this->client->request('GET', $this->orgPath());
        return $decoded['groups'] ?? $decoded;
    }

    /**
     * @param array<string>|string|null $extensions 'all', or an array of extension names from the contract enum.
     * @return array<mixed>
     */
    public function create(string $groupName, string $location, string|array|null $extensions = null): array
    {
        if (trim($groupName) === '') {
            throw new ValidationException('Group name must not be empty.');
        }
        if (trim($location) === '') {
            throw new ValidationException('Group location must not be empty.');
        }

        $body = ['name' => $groupName, 'location' => $location];
        if ($extensions !== null) {
            $body['extensions'] = $this->validateExtensions($extensions);
        }

        $decoded = $this->client->request('POST', $this->orgPath(), [], $body);
        return $decoded['group'] ?? $decoded;
    }

    /**
     * Get a group.
     * @return array<mixed>
     */
    public function get(string $groupName): array
    {
        $decoded = $this->client->request('GET', $this->orgPath('/' . rawurlencode($groupName)));
        return $decoded['group'] ?? $decoded;
    }

    /**
     * Delete a group.
     * @return array<mixed>
     */
    public function delete(string $groupName): array
    {
        $decoded = $this->client->request('DELETE', $this->orgPath('/' . rawurlencode($groupName)));
        return $decoded['group'] ?? $decoded;
    }

    /**
     * Get group configuration (delete_protection).
     * @return array<mixed>
     */
    public function getConfiguration(string $groupName): array
    {
        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('GET', $this->orgPath('/' . rawurlencode($groupName) . '/configuration'));
        return $decoded;
    }

    /**
     * Update group configuration. Only `delete_protection` is supported by the contract.
     *
     * @param array<string, bool> $configuration
     * @return array<mixed>
     */
    public function updateConfiguration(string $groupName, array $configuration): array
    {
        foreach (array_keys($configuration) as $key) {
            if ($key !== 'delete_protection') {
                throw new ValidationException('Unsupported configuration key: ' . $key . '. Only delete_protection is supported.');
            }
        }
        if (isset($configuration['delete_protection']) && !is_bool($configuration['delete_protection'])) {
            throw new ValidationException('delete_protection must be a boolean.');
        }

        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('PATCH',
            $this->orgPath('/' . rawurlencode($groupName) . '/configuration'),
            [],
            $configuration);
        return $decoded;
    }

    /**
     * Transfer a group to another organization.
     * @return array<mixed>
     */
    public function transfer(string $groupName, string $targetOrganization): array
    {
        if (trim($targetOrganization) === '') {
            throw new ValidationException('Target organization must not be empty.');
        }

        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('POST',
            $this->orgPath('/' . rawurlencode($groupName) . '/transfer'),
            [],
            ['organization' => $targetOrganization]);
        return $decoded;
    }

    /**
     * Unarchive a group.
     * @return array<mixed>
     */
    public function unarchive(string $groupName): array
    {
        $decoded = $this->client->request('POST',
            $this->orgPath('/' . rawurlencode($groupName) . '/unarchive'));
        return $decoded['group'] ?? $decoded;
    }

    /**
     * Add a location (and its replica) to a group.
     * @return array<mixed>
     */
    public function addLocation(string $groupName, string $location): array
    {
        $decoded = $this->client->request('POST',
            $this->orgPath('/' . rawurlencode($groupName) . '/locations/' . rawurlencode($location)));
        return $decoded['group'] ?? $decoded;
    }

    /**
     * Remove a location from a group.
     * @return array<mixed>
     */
    public function removeLocation(string $groupName, string $location): array
    {
        $decoded = $this->client->request('DELETE',
            $this->orgPath('/' . rawurlencode($groupName) . '/locations/' . rawurlencode($location)));
        return $decoded['group'] ?? $decoded;
    }

    /**
     * Update the database version of the group in place (200 No Content).
     * @return array<mixed>
     */
    public function update(string $groupName): array
    {
        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('POST',
            $this->orgPath('/' . rawurlencode($groupName) . '/update'));
        return $decoded;
    }

    /**
     * Create a group token.
     *
     * @param array<string> $attachDatabases Database names the token may attach (read).
     */
    public function createToken(
        string $groupName,
        string $expiration = 'never',
        Authorization $authorization = Authorization::FULL_ACCESS,
        array $attachDatabases = [],
    ): string {
        foreach ($attachDatabases as $db) {
            if (!is_string($db) || $db === '') {
                throw new ValidationException('attachDatabases must contain non-empty database names.');
            }
        }

        $query = [
            'expiration' => $expiration,
            'authorization' => $authorization->value,
        ];

        $body = [];
        if ($attachDatabases !== []) {
            $body = [
                'permissions' => [
                    'read_attach' => [
                        'databases' => array_values($attachDatabases),
                    ],
                ],
            ];
        }

        $decoded = $this->client->request('POST',
            $this->orgPath('/' . rawurlencode($groupName) . '/auth/tokens'),
            $query,
            $body === [] ? null : $body);

        if (isset($decoded['jwt']) && is_string($decoded['jwt'])) {
            return $decoded['jwt'];
        }

        throw new \Darkterminal\TursoPlatformAPI\Exceptions\UnexpectedResponseException('Expected a jwt string in the token creation response.');
    }

    /**
     * Invalidate (rotate) all tokens for a group.
     * @return array<mixed>
     */
    public function invalidateTokens(string $groupName): array
    {
        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('POST',
            $this->orgPath('/' . rawurlencode($groupName) . '/auth/rotate'));
        return $decoded;
    }

    /** Build the org-scoped path. */
    private function orgPath(string $segment = ''): string
    {
        $org = $this->client->organization();
        $base = '/v1/organizations/' . rawurlencode($org) . '/groups';
        return $segment === '' ? $base : $base . $segment;
    }

    /**
     * @param string|list<string> $extensions
     * @return string|array<string>
     */
    private function validateExtensions(string|array $extensions): string|array
    {
        if ($extensions === 'all') {
            return 'all';
        }
        if (!is_array($extensions) || $extensions === []) {
            throw new ValidationException('extensions must be "all" or a non-empty array of extension names.');
        }
        foreach ($extensions as $ext) {
            if (!is_string($ext) || !in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                throw new ValidationException('Unsupported extension: ' . var_export($ext, true) . '. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS) . ', or "all".');
            }
        }
        return array_values(array_unique($extensions));
    }
}
