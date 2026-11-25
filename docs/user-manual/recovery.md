---
title: Recovery (v3)
description: List and restore deleted Turso databases and groups using v3 recovery endpoints.
section: Resources
eyebrow: Resources
---

## Overview

`Recovery` wraps Turso Platform API v3 recovery operations. These endpoints use an **organization ID (UUID)**, not the organization slug.

Configure the client with both values when you need normal resources and recovery:

```php
$client = Client::builder()
    ->withToken($_ENV['TURSO_PLATFORM_API_TOKEN'])
    ->withOrganization('acme')                 // slug for v1/v2
    ->withOrganizationId('f1d2...uuid...')     // UUID for v3 recovery
    ->build();
```

If `organizationId` is missing, recovery methods throw `ConfigurationException` before making a request.

## Methods

| Method | Contract operation | Notes |
|---|---|---|
| `listDeletedDatabases(?string $search = null, int $limit = 20, ?string $cursor = null): array` | `listDeletedDatabases` | Supports search, limit, cursor. |
| `restoreDatabase(string $databaseId, ?string $name = null): array` | `restoreDatabase` | Restore deleted DB by ID; optional new name. |
| `listDeletedGroups(int $limit = 20, ?string $cursor = null): array` | `listDeletedGroups` | Paginated deleted groups. |
| `restoreGroup(string $groupId): array` | `restoreGroup` | Restore deleted group by ID. |

## List deleted databases

```php
$deleted = $client->recovery()->listDeletedDatabases(
    search: 'app',
    limit: 20,
);
```

Use the returned database IDs for restore operations. IDs are not the same as database names.

## Restore a database

```php
$restored = $client->recovery()->restoreDatabase(
    databaseId: 'db-uuid-or-id',
    name: 'app-db-restored', // optional
);
```

Choose a new name when restoring into an environment where the old name may conflict.

## List and restore deleted groups

```php
$groups = $client->recovery()->listDeletedGroups(limit: 20);
$restored = $client->recovery()->restoreGroup('group-id');
```

## Operational cautions

- Recovery operations are destructive/administrative in effect. Confirm IDs before calling restore.
- Audit the action in your own logs; also check `auditLogs()` where applicable.
- Prefer least-privilege tokens for automation that can restore resources.