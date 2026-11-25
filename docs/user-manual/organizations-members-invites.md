---
title: Organizations, members, and invites
description: Manage organization settings, plans, usage, members, roles, and v2 invites.
section: Resources
eyebrow: Resources
---

## Organizations

`Organizations` requires a default organization slug for organization-scoped methods. Set it with `withOrganization('slug')`.

### Methods

| Method | Contract operation | Notes |
|---|---|---|
| `list(): array` | `listOrganizations` | Lists organizations visible to the token. |
| `get(): array` | `getOrganization` | Gets the configured organization. |
| `update(array $settings): array` | `updateOrganization` | PATCH organization settings. |
| `listPlans(): array` | `listOrganizationPlans` | Lists available plans. |
| `listInvoices(string $type = 'all'): array` | `listOrganizationInvoices` | `type` must be `all`, `upcoming`, or `issued`. |
| `getSubscription(): array` | `getOrganizationSubscription` | Current subscription. |
| `getUsage(): array` | `getOrganizationUsage` | Organization usage. |

### Example

```php
$org = $client->organizations()->get();

$client->organizations()->update([
    'overages' => true,
]);

$invoices = $client->organizations()->listInvoices('issued');
$usage = $client->organizations()->getUsage();
```

`listInvoices()` validates `$type` locally and throws `ValidationException` for unsupported values.

## Members

`Members` manages organization members. Roles use `Darkterminal\TursoPlatformAPI\Enums\MemberRole`.

### Methods

| Method | Contract operation | Notes |
|---|---|---|
| `list(): array` | `listOrganizationMembers` | List members. |
| `add(?string $username = null, ?string $email = null, MemberRole $role = MemberRole::MEMBER): array` | `addOrganizationMember` | Requires username or email. |
| `get(string $username): array` | `getOrganizationMember` | Get member by username. |
| `updateRole(string $username, MemberRole $role): array` | `updateMemberRole` | PATCH member role. |
| `remove(string $username): array` | `removeOrganizationMember` | Remove member. |

### Example

```php
use Darkterminal\TursoPlatformAPI\Enums\MemberRole;

$members = $client->members()->list();

$client->members()->add(email: 'dev@example.com', role: MemberRole::MEMBER);
$client->members()->updateRole('alice', MemberRole::ADMIN);
$client->members()->remove('alice');
```

`add()` throws `ValidationException` when neither username nor email is provided.

## Invites

`Invites` uses the v2 organization invites endpoints.

### Methods

| Method | Contract operation | Notes |
|---|---|---|
| `list(): array` | `listOrganizationInvitesV2` | List invites. |
| `create(?string $email = null, ?string $username = null, MemberRole $role = MemberRole::MEMBER): array` | `inviteOrganizationMemberV2` | Requires username or email. |
| `delete(string $email): array` | `deleteOrganizationInviteByEmailV2` | Delete invite by email. |

### Example

```php
use Darkterminal\TursoPlatformAPI\Enums\MemberRole;

$invites = $client->invites()->list();
$client->invites()->create(email: 'newdev@example.com', role: MemberRole::MEMBER);
$client->invites()->delete('newdev@example.com');
```

## Role enum

```php
use Darkterminal\TursoPlatformAPI\Enums\MemberRole;

MemberRole::ADMIN;
MemberRole::MEMBER;
MemberRole::VIEWER;
```

Pass enum cases directly to SDK methods. Avoid passing raw strings unless you are building a boundary layer that converts them first.