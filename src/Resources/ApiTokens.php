<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Resources;

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Enums\ApiTokenScope;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;

/**
 * Personal API tokens resource — operationIds:
 * listAPITokens, createAPIToken, revokeAPIToken.
 *
 * Envelopes: list -> {tokens: [...]} | create -> {name, id, token}
 * revoke -> {token: ...}
 */
final class ApiTokens
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /** List personal API tokens. */
    public function list(): array
    {
        $decoded = $this->client->request('GET', $this->personalPath());
        return $decoded['tokens'] ?? $decoded;
    }

    /**
     * Create a personal API token.
     *
     * Contract: when `group` is set, `organization` and a non-empty `scopes` list
     * are required. When `group` is not set, `scopes` may be omitted (the token
     * inherits the user's permissions in the organization).
     *
     * @param list<ApiTokenScope|string>|null $scopes
     * @return array<mixed>
     */
    public function create(string $tokenName, ?string $organization = null, ?string $group = null, ?array $scopes = null): array
    {
        if (trim($tokenName) === '') {
            throw new ValidationException('Token name must not be empty.');
        }
        if (preg_match('/[\\/]/', $tokenName) === 1) {
            throw new ValidationException('Token name must not contain path separators.');
        }

        $normalizedScopes = $this->validateScopes($scopes);

        if ($group !== null) {
            if ($organization === null || $organization === '') {
                throw new ValidationException('An organization is required when a group is set.');
            }
            if ($normalizedScopes === null || $normalizedScopes === []) {
                throw new ValidationException('scopes must be non-empty when a group is set.');
            }
        }

        $body = [];
        if ($organization !== null) {
            $body['organization'] = $organization;
        }
        if ($group !== null) {
            $body['group'] = $group;
        }
        if ($normalizedScopes !== null) {
            $body['scopes'] = $normalizedScopes;
        }

        $decoded = $this->client->request('POST', $this->personalPath('/' . rawurlencode($tokenName)), [], $body);
        return $decoded;
    }

    /** Revoke (delete) a personal API token by name. */
    public function revoke(string $tokenName): array
    {
        if (trim($tokenName) === '') {
            throw new ValidationException('Token name must not be empty.');
        }
        if (preg_match('/[\\/]/', $tokenName) === 1) {
            throw new ValidationException('Token name must not contain path separators.');
        }
        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('DELETE', $this->personalPath('/' . rawurlencode($tokenName)));
        return $decoded;
    }

    /** @param list<ApiTokenScope|string>|null $scopes @return list<string>|null */
    private function validateScopes(?array $scopes): ?array
    {
        if ($scopes === null) {
            return null;
        }
        $out = [];
        $valid = array_map(fn (ApiTokenScope $s) => $s->value, ApiTokenScope::cases());
        foreach ($scopes as $s) {
            $value = $s instanceof ApiTokenScope ? $s->value : (string) $s;
            if (!in_array($value, $valid, true)) {
                throw new ValidationException('Unsupported scope: ' . $value . '. Allowed: ' . implode(', ', $valid) . '.');
            }
            $out[] = $value;
        }
        return $out;
    }

    private function personalPath(string $segment = ''): string
    {
        return '/v1/auth/api-tokens' . $segment;
    }
}
