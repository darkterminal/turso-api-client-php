<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Resources;

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;

/**
 * Recovery resource — v3 "restore" operations (operationIds:
 * listDeletedDatabases, restoreDatabase, listDeletedGroups, restoreGroup).
 *
 * These paths take the organization ID (UUID), not the organization slug.
 * Configure it with ClientBuilder::withOrganizationId() or
 * Configuration::__construct(organizationId: ...).
 *
 * Envelopes:
 *   listDeletedDatabases -> {databases: [...], pagination: {next: ?string}}
 *   restoreDatabase      -> {database: {...}}
 *   listDeletedGroups    -> {groups: [...], pagination: {next: ?string}}
 *   restoreGroup         -> {group: {...}, databases: [...], failures: [...]}
 */
final class Recovery
{
    private const DEFAULT_PAGE_SIZE = 20;

    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * List deleted databases.
     *
     * @param string $search Optional name fragment to filter by.
     * @return array<mixed>
     */
    public function listDeletedDatabases(?string $search = null, int $limit = self::DEFAULT_PAGE_SIZE, ?string $cursor = null): array
    {
        $this->assertLimit($limit);

        $query = ['deleted' => 'true', 'limit' => $limit];
        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }
        if ($cursor !== null && $cursor !== '') {
            $query['cursor'] = $cursor;
        }

        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('GET', $this->v3Path('databases'), $query);
        return $decoded;
    }

    /**
     * Restore a deleted database by ID.
     *
     * @param string|null $name Optional new name; defaults to the original name (hostname re-derived).
     * @return array<mixed>
     */
    public function restoreDatabase(string $databaseId, ?string $name = null): array
    {
        if (trim($databaseId) === '') {
            throw new ValidationException('Database ID must not be empty.');
        }
        if (preg_match('/[\\/]/', $databaseId) === 1) {
            throw new ValidationException('Database ID must not contain path separators.');
        }

        $body = [];
        if ($name !== null) {
            if (trim($name) === '') {
                throw new ValidationException('Restore name must not be empty when provided.');
            }
            $body['name'] = $name;
        }

        $decoded = $this->client->request('POST',
            $this->v3Path('databases/' . rawurlencode($databaseId) . '/restore'),
            [],
            $body === [] ? null : $body);
        return $decoded['database'] ?? $decoded;
    }

    /**
     * List deleted groups.
     * @return array<mixed>
     */
    public function listDeletedGroups(int $limit = self::DEFAULT_PAGE_SIZE, ?string $cursor = null): array
    {
        $this->assertLimit($limit);

        $query = ['limit' => $limit];
        if ($cursor !== null && $cursor !== '') {
            $query['cursor'] = $cursor;
        }

        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('GET', $this->v3Path('deleted-groups'), $query);
        return $decoded;
    }

    /**
     * Restore a deleted group by ID.
     * @return array<mixed>
     */
    public function restoreGroup(string $groupId): array
    {
        if (trim($groupId) === '') {
            throw new ValidationException('Group ID must not be empty.');
        }
        if (preg_match('/[\\/]/', $groupId) === 1) {
            throw new ValidationException('Group ID must not contain path separators.');
        }

        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('POST',
            $this->v3Path('groups/' . rawurlencode($groupId) . '/restore'));
        return $decoded;
    }

    private function v3Path(string $segment): string
    {
        return '/v3/organizations/' . rawurlencode($this->client->organizationId()) . '/' . ltrim($segment, '/');
    }

    private function assertLimit(int $limit): void
    {
        if ($limit < 1 || $limit > 100) {
            throw new ValidationException('Limit must be between 1 and 100.');
        }
    }
}
