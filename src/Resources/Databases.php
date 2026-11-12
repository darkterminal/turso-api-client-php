<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Resources;

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Enums\Authorization;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;

/**
 * Databases resource — 12 operations pinned in openapi/coverage-manifest.yaml
 * (operationIds: listDatabases, createDatabase, getDatabase, deleteDatabase,
 * getDatabaseConfiguration, updateDatabaseConfiguration, listDatabaseInstances,
 * getDatabaseInstance, createDatabaseToken, invalidateDatabaseTokens,
 * getDatabaseUsage, getDatabaseStats).
 *
 * Responses are returned verbatim as decoded JSON (associative arrays), matching
 * the envelope documented in the pinned OpenAPI snapshot:
 *   list -> {databases: [...]} | create/get/delete -> {database: {...}}
 *   configuration -> {size_limit, ...} | instances -> {instances: [...]}
 *   createToken -> {jwt: '...'} | usage -> {database: {...}} | stats -> {top_queries: [...]}
 * Unknown response fields are preserved (forward compatibility).
 */
final class Databases
{
    /** @var list<string> Configuration keys accepted by updateConfiguration(). */
    private const ALLOWED_CONFIGURATION_KEYS = [
        'size_limit', 'allow_attach', 'block_reads', 'block_writes',
        'delete_protection', 'allowed_ips', 'allowed_aws_vpc_ids',
    ];

    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * List databases in the organization.
     *
     * @return array<mixed>
     */
    public function list(?string $group = null, ?string $schema = null, ?string $parent = null): array
    {
        $query = array_filter([
            'group' => $group,
            'schema' => $schema,
            'parent' => $parent,
        ], fn ($v) => $v !== null);

        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('GET', $this->orgPath('databases'), $query);
        return $decoded['databases'] ?? $decoded;
    }

    /**
     * Create a database. `name` and `group` are required by the contract.
     *
     * @param array<string, mixed> $seed Optional seed: {type: 'database'|'database_upload', name?: string, timestamp?: string}
     * @param array<string, mixed>|null $remoteEncryption {encryption_key, encryption_cipher}
     * @return array<mixed>
     */
    public function create(
        string $name,
        string $group,
        ?array $seed = null,
        ?string $sizeLimit = null,
        ?array $remoteEncryption = null,
    ): array {
        $org = $this->client->organization();
        $this->assertDatabaseName($name);

        if (trim($group) === '') {
            throw new ValidationException('Database group must not be empty. The group must already exist.');
        }

        $body = [
            'name' => $name,
            'group' => $group,
        ];

        if ($seed !== null) {
            $body['seed'] = $this->validateSeed($seed);
        }
        if ($sizeLimit !== null && $sizeLimit !== '') {
            $body['size_limit'] = $sizeLimit;
        }
        if ($remoteEncryption !== null) {
            $body['remote_encryption'] = $this->validateRemoteEncryption($remoteEncryption);
        }

        $decoded = $this->client->request('POST',
            $this->orgPath('databases'),
            [],
            $body,
        );

        return $decoded['database'] ?? $decoded;
    }

    /** Get a database by name. */
    public function get(string $databaseName): array
    {
        $decoded = $this->client->request('GET',
            $this->orgPath('databases/' . rawurlencode($databaseName)));
        return $decoded['database'] ?? $decoded;
    }

    /**
     * Delete a database by name.
     *
     * Contract: the response envelope is {"database": "<name>"} — `database` is
     * a scalar string (the deleted database's name), NOT an object. We return
     * the scalar when it is present, or the verbatim response otherwise.
     */
    public function delete(string $databaseName): string|array
    {
        $decoded = $this->client->request('DELETE',
            $this->orgPath('databases/' . rawurlencode($databaseName)));
        if (isset($decoded['database']) && is_string($decoded['database'])) {
            return $decoded['database'];
        }
        return $decoded;
    }

    /** Get database configuration. */
    public function getConfiguration(string $databaseName): array
    {
        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('GET',
            $this->orgPath('databases/' . rawurlencode($databaseName) . '/configuration'));
        return $decoded;
    }

    /**
     * Update database configuration.
     *
     * Allowed keys only (unknown keys throw ValidationException):
     * size_limit, allow_attach (deprecated), block_reads, block_writes,
     * delete_protection, allowed_ips, allowed_aws_vpc_ids.
     *
     * @param array<string, mixed> $configuration
     * @return array<mixed>
     */
    public function updateConfiguration(string $databaseName, array $configuration): array
    {
        $this->assertConfigurationKeys($configuration);

        $decoded = $this->client->request('PATCH',
            $this->orgPath('databases/' . rawurlencode($databaseName) . '/configuration'),
            [],
            $configuration);
        return $decoded;
    }

    /** List instances for a database. */
    public function listInstances(string $databaseName): array
    {
        $decoded = $this->client->request('GET',
            $this->orgPath('databases/' . rawurlencode($databaseName) . '/instances'));
        return $decoded['instances'] ?? $decoded;
    }

    /** Get a specific instance. */
    public function getInstance(string $databaseName, string $instanceName): array
    {
        $decoded = $this->client->request('GET',
            $this->orgPath('databases/' . rawurlencode($databaseName) . '/instances/' . rawurlencode($instanceName)));
        return $decoded['instance'] ?? $decoded;
    }

    /**
     * Create a database token.
     *
     * Contract: expiration + authorization travel as query params; the
     * optional permissions object travels as JSON body (read_attach).
     *
     * @param array<string> $attachDatabases Database names the token may attach (read) when set.
     * @return string The raw JWT (decoded from the contract's {jwt: ...}).
     */
    public function createToken(
        string $databaseName,
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
            $this->orgPath('databases/' . rawurlencode($databaseName) . '/auth/tokens'),
            $query,
            $body === [] ? null : $body);

        return $this->extractJwt($decoded);
    }

    /** Invalidate (rotate) all tokens for a database. */
    public function invalidateTokens(string $databaseName): array
    {
        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('POST',
            $this->orgPath('databases/' . rawurlencode($databaseName) . '/auth/rotate'));
        return $decoded;
    }

    /**
     * Database usage for a period (from/to are opaque strings per contract, e.g. ISO-8601 dates).
     *
     * @return array<mixed>
     */
    public function usage(string $databaseName, ?string $from = null, ?string $to = null): array
    {
        $query = array_filter(['from' => $from, 'to' => $to], fn ($v) => $v !== null);
        $decoded = $this->client->request('GET',
            $this->orgPath('databases/' . rawurlencode($databaseName) . '/usage'),
            $query);
        return $decoded['database'] ?? $decoded;
    }

    /** Database top queries (by rows read/written). */
    public function stats(string $databaseName): array
    {
        $decoded = $this->client->request('GET',
            $this->orgPath('databases/' . rawurlencode($databaseName) . '/stats'));
        return $decoded['top_queries'] ?? $decoded;
    }

    /** Build the org-scoped path segment. */
    private function orgPath(string $segment): string
    {
        $org = $this->client->organization();
        return '/v1/organizations/' . rawurlencode($org) . '/' . ltrim($segment, '/');
    }

    private function assertDatabaseName(string $name): void
    {
        if (preg_match('/^[a-z0-9-]{1,64}$/', $name) !== 1) {
            throw new ValidationException('Database name must contain only lowercase letters, numbers and hyphens, and be at most 64 characters.');
        }
    }

    /** @param array<string, mixed> $seed */
    private function validateSeed(array $seed): array
    {
        $type = $seed['type'] ?? null;
        if (!is_string($type) || !in_array($type, ['database', 'database_upload'], true)) {
            throw new ValidationException("Seed 'type' must be 'database' or 'database_upload'.");
        }

        $allowed = ['type', 'name', 'timestamp'];
        foreach (array_keys($seed) as $key) {
            if (!in_array($key, $allowed, true)) {
                throw new ValidationException("Unsupported seed key: {$key}. Allowed: type, name, timestamp.");
            }
        }

        if ($type === 'database' && (empty($seed['name']) || !is_string($seed['name']))) {
            throw new ValidationException("Seed 'name' is required when seed type is 'database'.");
        }

        if (isset($seed['timestamp'])) {
            $ts = $seed['timestamp'];
            if (!is_string($ts) || strtotime($ts) === false) {
                throw new ValidationException("Seed 'timestamp' must be a parseable ISO 8601 datetime string.");
            }
        }

        return $seed;
    }

    /** @param array<string, mixed> $remoteEncryption */
    private function validateRemoteEncryption(array $remoteEncryption): array
    {
        $allowed = ['encryption_key', 'encryption_cipher'];
        foreach (array_keys($remoteEncryption) as $key) {
            if (!in_array($key, $allowed, true)) {
                throw new ValidationException("Unsupported remote_encryption key: {$key}. Allowed: encryption_key, encryption_cipher.");
            }
        }
        if (empty($remoteEncryption['encryption_key']) || empty($remoteEncryption['encryption_cipher'])) {
            throw new ValidationException('remote_encryption requires both encryption_key and encryption_cipher.');
        }
        $ciphers = ['aes256gcm', 'aes128gcm', 'chacha20poly1305', 'aegis128l', 'aegis128x2', 'aegis128x4', 'aegis256', 'aegis256x2', 'aegis256x4'];
        if (!in_array($remoteEncryption['encryption_cipher'], $ciphers, true)) {
            throw new ValidationException('remote_encryption encryption_cipher must be one of: ' . implode(', ', $ciphers));
        }
        return $remoteEncryption;
    }

    /** @param array<string, mixed> $configuration */
    private function assertConfigurationKeys(array $configuration): void
    {
        foreach (array_keys($configuration) as $key) {
            if (!in_array($key, self::ALLOWED_CONFIGURATION_KEYS, true)) {
                throw new ValidationException("Unsupported configuration key: {$key}. Allowed: " . implode(', ', self::ALLOWED_CONFIGURATION_KEYS) . '.');
            }
        }

        if (isset($configuration['allowed_ips']) && !is_array($configuration['allowed_ips'])) {
            throw new ValidationException('allowed_ips must be an array of IP/CIDR strings.');
        }
        if (isset($configuration['allowed_aws_vpc_ids']) && !is_array($configuration['allowed_aws_vpc_ids'])) {
            throw new ValidationException('allowed_aws_vpc_ids must be an array of vpce-* strings.');
        }
    }

    /** @param array<mixed> $decoded */
    private function extractJwt(array $decoded): string
    {
        if (isset($decoded['jwt']) && is_string($decoded['jwt'])) {
            return $decoded['jwt'];
        }
        throw new \Darkterminal\TursoPlatformAPI\Exceptions\UnexpectedResponseException('Expected a jwt string in the token creation response.');
    }
}
