# Changelog

All notable changes to this project are documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [2.0.0] — Unreleased

Complete rewrite. The v1 public API is removed entirely.

### Added

- Framework-independent typed client built on PSR-18/PSR-17/PSR-7.
- `Client::builder()` entry point with `withToken`, `withOrganization`, `withOrganizationId`, `withBaseUrl`, `withTimeout`, `withTransport`.
- Resource-oriented API mapping 1:1 to the pinned OpenAPI snapshot (SHA-256 `328d56b8…`): 53/53 operations implemented.
- v3 Recovery operations (`listDeletedDatabases`, `restoreDatabase`, `listDeletedGroups`, `restoreGroup`) configurable via `withOrganizationId`.
- Exception hierarchy rooted at `PlatformException`: `ConfigurationException`, `TransportException`, `HttpException`, `AuthenticationException`, `NotFoundException`, `RateLimitException` (with `retryAfter`), `ServerException`, `ValidationException`, `UnexpectedResponseException`.
- Frozen OpenAPI snapshot under `openapi/snapshots/` + `openapi/coverage-manifest.yaml` mapping every operation to a resource and implementation status.
- Default `CurlTransport` (PSR-18) with no redirect-following and a strict response-body parser.

### Changed

- Client is constructed exclusively via `Client::builder()` — the v1 positional `new Client(org, token)` signature is gone.
- Error handling never echoes credentials and never calls `die()`/`exit()`; all failures throw typed exceptions.
- Authorization carries bearer tokens with `Content-Length` handling; empty 2xx bodies are accepted as empty arrays (e.g. token-rotation endpoints).

### Removed

- All v1 classes under `Darkterminal\TursoPlatformAPI\core\*` (re-implementations, see the migration notes at the bottom of this file).
- `docs/` v1 content — replaced with the v2 API map and architecture notes.

### Migration guide from v1

```php
// Before (v1):
$client = new Client('org-name', 'token');
$client->databases()->list();

// After (v2):
$client = Client::builder()
    ->withToken('token')
    ->withOrganization('org-name')   // slug, not org ID (UUID) — use withOrganizationId() only for v3 recovery operations
    ->build();
$client->databases()->list();
```

`createToken` changed signature: it now returns `string` (JWT) instead of an object, and `expiration`/`authorization` travel as query parameters per contract.

Breaking changes are deliberate; consumers should review the new [API map](docs/api-map.md) and update their calls.
