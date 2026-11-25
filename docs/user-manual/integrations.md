---
title: Integrations
description: Use the standalone DatabaseUploader and RegionResolver integrations.
section: Guide
eyebrow: Integrations
---

## Overview

The SDK ships two standalone integrations under `Darkterminal\TursoPlatformAPI\Integrations`. They are intentionally separate from the main Platform API resource client because they have different credentials, endpoints, and safety constraints.

## DatabaseUploader

`DatabaseUploader` streams a local SQLite dump to Turso's upload endpoint.

### When to use it

Use it after creating a database with `seed.type = 'database_upload'`:

```php
$created = $client->databases()->create(
    name: 'uploaded-db',
    group: 'default',
    seed: ['type' => 'database_upload'],
);
```

The create response contains upload details/host as defined by the Platform API contract. Use the upload host and a database-scoped token/JWT as required by the upload flow.

### Safety model

The uploader enforces preflight checks before sending bytes:

- File exists and is readable.
- SQLite `journal_mode` is `WAL`.
- `page_size` is `4096`.
- `auto_vacuum` is `0`.
- `encoding` is UTF-8.

It also validates upload host scheme. HTTP upload hosts are rejected before credentials are attached.

### Example

```php
use Darkterminal\TursoPlatformAPI\Integrations\DatabaseUploader;

$uploader = new DatabaseUploader();

$result = $uploader->uploadDump(
    hostname: 'dmpupload-....turso.io',
    databaseToken: $databaseJwt,
    dumpPath: '/path/to/dump.sqlite',
);
```

Optional encryption headers can be passed when uploading encrypted databases, as supported by the integration contract.

### Common failure

If preflight fails, rebuild the dump with required PRAGMA settings:

```sql
PRAGMA journal_mode=WAL;
PRAGMA page_size=4096;
PRAGMA auto_vacuum=0;
PRAGMA encoding='UTF-8';
```

## RegionResolver

`RegionResolver` calls `https://region.turso.io/` to determine the closest region for the caller.

```php
use Darkterminal\TursoPlatformAPI\Integrations\RegionResolver;

$resolver = new RegionResolver();
$closest = $resolver->closest();

// ['server' => 'aws-us-west-2', 'client' => 'lax']
```

### Behavior

- No Platform API token required.
- POST is attempted; GET fallback may be used depending on implementation behavior.
- Transport/network failures throw SDK integration exceptions; it does not guess a region.
- The `server` field is the resolver server region; `client` is the caller's airport/location code as reported by the service.

Use `closest()` for selecting a nearby group/database location candidate, then validate against `locations()->list()` or your deployment policy.