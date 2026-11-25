---
title: API tokens and audit logs
description: Manage personal and organization-scoped Platform API tokens, and read organization audit logs.
section: Resources
eyebrow: Resources
---

## Personal API tokens

`ApiTokens` manages personal Platform API tokens (`/v1/auth/api-tokens`).

### Methods

| Method | Contract operation | Notes |
|---|---|---|
| `list(): array` | `listAPITokens` | Lists personal API tokens. |
| `create(string $tokenName, ?string $organization = null, ?string $group = null, ?array $scopes = null): array` | `createAPIToken` | Creates a token; scopes may be enum/string values depending on SDK input handling. |
| `revoke(string $tokenName): array` | `revokeAPIToken` | Revokes token by name. |

### Example

```php
use Darkterminal\TursoPlatformAPI\Enums\ApiTokenScope;

$tokens = $client->apiTokens()->list();

$created = $client->apiTokens()->create(
    tokenName: 'ci-token',
    organization: 'acme',
    scopes: [ApiTokenScope::READ_ONLY],
);

$client->apiTokens()->revoke('ci-token');
```

The create response includes the token value as returned by the Platform API. Capture it immediately; it may not be retrievable later.

## Organization API tokens

`OrganizationApiTokens` manages org-scoped tokens (`/v1/organizations/{organizationSlug}/api-tokens`).

### Methods

| Method | Contract operation | Notes |
|---|---|---|
| `list(): array` | `listOrganizationAPITokens` | List org-scoped tokens. |
| `revoke(string $tokenId): array` | `revokeOrganizationAPIToken` | Revoke by token ID. |

### Example

```php
$tokens = $client->organizationApiTokens()->list();
$client->organizationApiTokens()->revoke('token-id');
```

## Audit logs

`AuditLogs` reads organization audit logs.

### Method

| Method | Contract operation | Notes |
|---|---|---|
| `list(int $page = 1, int $pageSize = 25): array` | `listOrganizationAuditLogs` | Paginated audit log list. |

### Example

```php
$logs = $client->auditLogs()->list(page: 1, pageSize: 25);
```

Use audit logs for security review and operational traceability after member, token, database, or group changes.

## Token scope enum

`ApiTokenScope` includes contract scopes such as:

- `READ`, `READ_ONLY`, `FULL_ACCESS`
- `DB_CREATE`, `DB_DELETE`, `DB_CONFIGURE`, `DB_MINT_TOKEN`, `DB_ROTATE_CREDS`
- `GROUP_CREATE`, `GROUP_DELETE`, `GROUP_CONFIGURE`, `GROUP_MINT_TOKEN`, `GROUP_ROTATE_CREDS`
- `ORG_READ`

See [Enums reference](reference/enums.md) for the full list.