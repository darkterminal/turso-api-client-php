<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Resources;

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Enums\MemberRole;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;

/**
 * Members resource — 5 operations pinned in openapi/coverage-manifest.yaml
 * (operationIds: listOrganizationMembers, addOrganizationMember,
 * getOrganizationMember, updateMemberRole, removeOrganizationMember).
 *
 * Envelopes:
 *   list -> {members: [Member]}  |  add/updateRole -> {member, role}
 *   get -> {member: {...}}       |  remove -> {member: username}
 */
final class Members
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * @return array<mixed>
     */
    public function list(): array
    {
        $decoded = $this->client->request('GET', $this->orgPath('/members'));
        return $decoded['members'] ?? $decoded;
    }

    /**
     * Add a member. Exactly one of `username` or `email` must be provided.
     * @return array<mixed>
     */
    public function add(?string $username = null, ?string $email = null, MemberRole $role = MemberRole::MEMBER): array
    {
        if (($username === null || trim($username) === '') && ($email === null || trim($email) === '')) {
            throw new ValidationException('Provide a username or an email address.');
        }
        if ($username !== null && $email !== null) {
            throw new ValidationException('Provide either a username or an email address, not both.');
        }

        $body = ['role' => $role->value];
        if ($username !== null) {
            $body['username'] = $username;
        } else {
            $body['email'] = $email;
        }

        // Contract envelope: {"member": string(username), "role": string} — member is a scalar here,
        // so we return the verbatim envelope rather than unwrapping a scalar.
        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('POST', $this->orgPath('/members'), [], $body);
        return $decoded;
    }

    /**
     * Get a single member. Unwraps the Member object from the envelope.
     * @return array<mixed>
     */
    public function get(string $username): array
    {
        if (trim($username) === '') {
            throw new ValidationException('Username must not be empty.');
        }
        $decoded = $this->client->request('GET', $this->orgPath('/members/' . rawurlencode($username)));
        return $decoded['member'] ?? $decoded;
    }

    /**
     * Update a member's role.
     * @return array<mixed>
     */
    public function updateRole(string $username, MemberRole $role): array
    {
        if (trim($username) === '') {
            throw new ValidationException('Username must not be empty.');
        }
        $decoded = $this->client->request('PATCH',
            $this->orgPath('/members/' . rawurlencode($username)),
            [],
            ['role' => $role->value]);
        return $decoded['member'] ?? $decoded;
    }

    /**
     * Remove a member. Contract envelope: {"member": string(username)} (member is a scalar here).
     * @return array<mixed>
     */
    public function remove(string $username): array
    {
        if (trim($username) === '') {
            throw new ValidationException('Username must not be empty.');
        }
        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('DELETE',
            $this->orgPath('/members/' . rawurlencode($username)));
        return $decoded;
    }

    private function orgPath(string $segment = ''): string
    {
        $org = $this->client->organization();
        $base = '/v1/organizations/' . rawurlencode($org);
        return $segment === '' ? $base : $base . $segment;
    }
}
