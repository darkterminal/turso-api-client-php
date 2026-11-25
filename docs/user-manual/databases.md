---
title: Databases
description: Create, inspect, configure, token-manage, and observe Turso databases through the Platform API.
section: Resources
eyebrow: Resources
---

## Overview

`Databases` covers the database lifecycle and operational metadata exposed by the Turso Platform API. Most methods require a configured organization slug.

```php
$databases = $client->databases();
```

## Methods

| Method | Contract operation | Return |
|---|---|---|
| `list(?string $group = null, ?string $schema = null, ?string $parent = null): array` | `listDatabases` | `['databases' => [...]]` |
| `create(string $name, string $group, ?array $seed = null, ?string $sizeLimit = null, ?array $remoteEncryption = null): array` | `createDatabase` | `['database' => {...}]` |
| `get(string $databaseName): array` | `getDatabase` | `['database' => {...}]` |
| `delete(string $databaseName): string|array` | `deleteDatabase` | deleted name string or verbatim array |
| `getConfiguration(string $databaseName): array` | `getDatabaseConfiguration` | config payload |
| `updateConfiguration(string $databaseName, array $configuration): array` | `updateDatabaseConfiguration` | updated config payload |
| `listInstances(string $databaseName): array` | `listDatabaseInstances` | `['instances' => [...]]` |
| `getInstance(string $databaseName, string $instanceName): array` | `getDatabaseInstance` | `['instance' => {...}]` |
| `createToken(string $databaseName, string $expiration = 'never', Authorization $authorization = Authorization::FULL_ACCESS, array $attachDatabases = []): array` | `createDatabaseToken` | `['jwt' => '...']` |
| `invalidateTokens(string $databaseName): array` | `invalidateDatabaseTokens` | `[]` on empty 2xx body |
| `usage(string $databaseName, ?string $from = null, ?string $to = null): array` | `getDatabaseUsage` | usage payload |
| `stats(string $databaseName): array` | `getDatabaseStats` | stats payload |

## Create database

```php
$created = $client->databases()->create(
    name: 'app-db',
    group: 'default',
);
```

With seed:

```php
$created = $client->databases()->create(
    name: 'copy-db',
    group: 'default',
    seed: ['type' => 'database', 'name' => 'app-db'],
);
```

For upload workflows, create with `seed.type = 'database_upload'`, then use `Integrations\DatabaseUploader`.

## Configuration

Only allowed configuration keys can be sent by `updateConfiguration()`:

- `size_limit`
- `allow_attach`
- `block_reads`
- `block_writes`
- `delete_protection`

```php
$config = $client->databases()->getConfiguration('app-db');

$updated = $client->databases()->updateConfiguration('app-db', [
    'delete_protection' => true,
]);
```

Unknown keys throw `ValidationException` before the request.

## Database tokens

```php
use Darkterminal\TursoPlatformAPI\Enums\Authorization;

$token = $client->databases()->createToken(
    databaseName: 'app-db',
    expiration: 'never',
    authorization: Authorization::READ_ONLY,
);

$jwt = $token['jwt'];
```

Token parameters:

- `expiration` — contract-defined expiration string; default `never`.
- `authorization` — `Authorization::FULL_ACCESS` or `Authorization::READ_ONLY`.
- `attachDatabases` — list of attach database permissions as expected by the contract.

Rotate/invalidate tokens:

```php
$client->databases()->invalidateTokens('app-db');
```

This endpoint may return an empty 2xx body. The SDK normalizes empty success bodies to `[]`.

## Usage and stats

```php
$usage = $client->databases()->usage('app-db', from: '2026-01-01', to: '2026-01-31');
$stats = $client->databases()->stats('app-db');
```

`stats()` returns the top-queries/stats payload from the Platform API. Treat the structure as contract-defined and preserve unknown fields.

## Delete caveat

`deleteDatabase` has a contract quirk: the response envelope can be `{"database":"name"}` where `database` is a string, not an object.

The SDK returns `string|array`:

```php
$result = $client->databases()->delete('app-db');
// 'app-db' or raw decoded array
```

Do not assume an object shape on delete.