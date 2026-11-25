---
title: Overview
description: What the Turso Platform PHP SDK is, what it covers, and how the documentation is organized.
section: Introduction
eyebrow: User manual
---

## What is this SDK?

**Turso Platform PHP SDK** (`darkterminal/turso-api-client-php`) is a typed PHP client for the [Turso Platform API](https://docs.turso.tech/api-reference/introduction). It gives you resource-oriented methods for managing Turso organizations, databases, groups, tokens, invites, audit logs, and recovery workflows from PHP applications, CLI tools, and automation jobs.

The SDK is not a database driver. It does not execute SQL over libSQL/Hrana. Use it for **control-plane** operations — provisioning, configuration, token management, and platform metadata. For data-plane access, use a libSQL/Turso database client after creating database tokens through this SDK.

## What it covers

The client maps 53 operations from a pinned OpenAPI snapshot (`openapi/coverage-manifest.yaml`) across these resources:

| Resource | Purpose | Key methods |
|---|---|---|
| `user()` | Current authenticated user | `get()` |
| `auth()` | Token validation | `validate()` |
| `locations()` | Available Turso locations | `list()` |
| `organizations()` | Organization settings, plans, invoices, subscription, usage | `list()`, `get()`, `update()`, `getUsage()` |
| `members()` | Organization members and roles | `list()`, `add()`, `updateRole()`, `remove()` |
| `invites()` | Organization invites (v2) | `list()`, `create()`, `delete()` |
| `databases()` | Database lifecycle, config, instances, tokens, usage, stats | `create()`, `createToken()`, `stats()` |
| `groups()` | Group lifecycle, locations, config, transfer, tokens | `create()`, `addLocation()`, `createToken()` |
| `apiTokens()` | Personal Platform API tokens | `list()`, `create()`, `revoke()` |
| `organizationApiTokens()` | Organization-scoped Platform API tokens | `list()`, `revoke()` |
| `auditLogs()` | Organization audit log pages | `list()` |
| `recovery()` | v3 deleted database/group restore | `listDeletedDatabases()`, `restoreDatabase()` |

Standalone integrations live under `Darkterminal\TursoPlatformAPI\Integrations`:

- **`DatabaseUploader`** — streams a local SQLite dump to Turso's upload endpoint with preflight checks.
- **`RegionResolver`** — resolves the closest region via `https://region.turso.io/`.

## Design principles

- **Typed resource methods.** Public methods are explicit; inputs are validated before HTTP calls where the contract is clear.
- **PSR-18 transport.** Default transport uses `ext-curl`; you can inject any `Psr\Http\Client\ClientInterface`.
- **Immutable builder/config.** `ClientBuilder` returns cloned instances; `Configuration` validates token, base URL, timeout, and organization fields.
- **Predictable exceptions.** HTTP and validation failures map to specific SDK exception classes.
- **Raw payload preservation.** Decoded JSON responses are returned as arrays/values matching the contract envelope; unknown fields are preserved for forward compatibility.

## Requirements

- PHP 8.3 or newer
- `ext-curl`
- `ext-json`
- Composer

Development dependencies include Pest and PHPStan.

## Documentation map

- [Getting started](getting-started.md) — install, create token, first request.
- [Configuration](configuration.md) — builder options, PSR-18 transport, timeouts, organization IDs.
- Resource pages — method signatures, parameters, return shapes, and caveats.
- [Integrations](integrations.md) — database upload and region resolver.
- [Errors and troubleshooting](errors-troubleshooting.md) — exception hierarchy and real-world fixes.
- [API map](reference/api-map.md) — operationId-to-method coverage table.

## Source of truth

When docs and behavior disagree, trust this order:

1. The SDK source in `src/` and tests in `tests/`.
2. The pinned OpenAPI snapshot and `openapi/coverage-manifest.yaml`.
3. This manual.
4. `README.md` for quick onboarding.

If you find a mismatch, fix the code/docs and keep the contract mapping explicit.