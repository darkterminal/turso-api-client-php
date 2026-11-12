<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Resources;

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Enums\MemberRole;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;

/**
 * Invites resource — v2 operations (operationIds: listOrganizationInvitesV2,
 * inviteOrganizationMemberV2, deleteOrganizationInviteByEmailV2).
 *
 * Envelopes:
 *   list -> {invites: [InviteV2]}  |  invite -> {invited: InviteCreatedV2}
 *   delete -> 200 No Content
 */
final class Invites
{
    public function __construct(
        private readonly Client $client,
    ) {}

    public function list(): array
    {
        $decoded = $this->client->request('GET', $this->orgPath('/v2/organizations', '/invites'));
        return $decoded['invites'] ?? $decoded;
    }

    /**
     * Invite a user. Exactly one of `email` or `username` must be provided.
     */
    public function create(?string $email = null, ?string $username = null, MemberRole $role = MemberRole::MEMBER): array
    {
        if (($email === null || trim($email) === '') && ($username === null || trim($username) === '')) {
            throw new ValidationException('Provide an email or a username.');
        }
        if ($email !== null && $username !== null) {
            throw new ValidationException('Provide either an email or a username, not both.');
        }

        $body = ['role' => $role->value];
        if ($email !== null) {
            $body['email'] = $email;
        } else {
            $body['username'] = $username;
        }

        $decoded = $this->client->request('POST', $this->orgPath('/v2/organizations', '/invites'), [], $body);
        return $decoded['invited'] ?? $decoded;
    }

    /** Delete an invite by email. */
    public function delete(string $email): array
    {
        if (trim($email) === '') {
            throw new ValidationException('Email must not be empty.');
        }
        $decoded = $this->client->request('DELETE',
            $this->orgPath('/v2/organizations', '/invites/' . rawurlencode($email)));
        return $decoded;
    }

    private function orgPath(string $prefix, string $segment = ''): string
    {
        $org = $this->client->organization();
        $base = $prefix . '/' . rawurlencode($org);
        return $segment === '' ? $base : $base . $segment;
    }
}
