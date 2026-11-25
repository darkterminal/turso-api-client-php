---
title: Enums reference
description: Enum values used by the Turso Platform PHP SDK.
section: Reference
eyebrow: Reference
---

## Authorization

Namespace: `Darkterminal\TursoPlatformAPI\Enums\Authorization`

Used by database/group token creation.

| Case | Value | Meaning |
|---|---|---|
| `FULL_ACCESS` | `full-access` | Full access token. |
| `READ_ONLY` | `read-only` | Read-only token. |

```php
use Darkterminal\TursoPlatformAPI\Enums\Authorization;

$client->databases()->createToken('app-db', authorization: Authorization::READ_ONLY);
```

## MemberRole

Namespace: `Darkterminal\TursoPlatformAPI\Enums\MemberRole`

Used by members and invites.

| Case | Value |
|---|---|
| `ADMIN` | `admin` |
| `MEMBER` | `member` |
| `VIEWER` | `viewer` |

```php
use Darkterminal\TursoPlatformAPI\Enums\MemberRole;

$client->members()->add(email: 'dev@example.com', role: MemberRole::MEMBER);
```

## ApiTokenScope

Namespace: `Darkterminal\TursoPlatformAPI\Enums\ApiTokenScope`

Used by personal API token creation scopes.

| Case | Value |
|---|---|
| `READ` | `read` |
| `DB_CREATE` | `db:create` |
| `DB_DELETE` | `db:delete` |
| `DB_CONFIGURE` | `db:configure` |
| `DB_MINT_TOKEN` | `db:mint-token` |
| `DB_ROTATE_CREDS` | `db:rotate-creds` |
| `GROUP_CONFIGURE` | `group:configure` |
| `GROUP_MINT_TOKEN` | `group:mint-token` |
| `GROUP_ROTATE_CREDS` | `group:rotate-creds` |
| `GROUP_CREATE` | `group:create` |
| `GROUP_DELETE` | `group:delete` |
| `ORG_READ` | `org:read` |
| `READ_ONLY` | `read-only` |
| `FULL_ACCESS` | `full-access` |

```php
use Darkterminal\TursoPlatformAPI\Enums\ApiTokenScope;

$client->apiTokens()->create('ci', scopes: [ApiTokenScope::READ_ONLY]);
```