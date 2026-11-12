# Changelog

All notable changes to this project are documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [2.1.0] — Released 2026-11-12

### Added

- `Integrations\DatabaseUploader` — standalone streaming uploader for a SQLite dump into a database created with `seed.type = 'database_upload'`. Pre-flight checks the dump (`journal_mode=wal`, `page_size=4096`, `auto_vacuum=0`, UTF-8) before transferring with streamed `CURLOPT_UPLOAD`/`INFILE`; supports optional `x-turso-encryption-key`/`x-turso-encryption-cipher` headers. Verified end-to-end against Turso Cloud 2026-11-12.
- `Integrations\RegionResolver` — standalone client for `https://region.turso.io/` (`closest()`; POST with GET fallback, no auth). Verified live 2026-11-12.
- `docker/upload-proof/` live harness: builds a clean PHP app (8.3/8.4/8.5) path-requiring this checkout, creates a scratch database, uploads an SQLite dump, reads the smoke table back over Hrana, and deletes the scratch database. `UPLOAD-PROOF-MATRIX: all green` on all three PHP versions.
- PHPStan (`level 5`) with `phpstan/phpstan-phpunit`; `phpstan.neon` ships in-repo.
- CI: `.github/workflows/tests.yml` — matrix PHP 8.3/8.4/8.5 running pytest-equivalent `pest`, PHPStan, and `composer audit || true`.
- Composer scripts: `test`, `analyse`.
- Hermetic tests: `tests/IntegrationsTest.php` uses unique `sys_get_temp_dir()` paths (works on CI runners); SQLite handles are closed before file-size assertions and WAL/SHM/journal sidecars are cleaned up.

### Fixed

- `Databases::delete()` contract mismatch: the live API returns `{"database": "<string-name>"}` (a scalar), not an object. The method now returns `string|array` and returns the scalar when present (previously threw a `TypeError`). Test uses the contract-correct `{"database": "db1"}` shape.
- README: dead links fixed, marketing language toned down.

## [2.0.0] — Released 2026-11-12

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
