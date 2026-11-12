# Troubleshooting

Only symptoms that have been observed and fixed in this repository (tests, CI, live Turso Cloud proofs) are listed here.

## Pre-flight dump checks for database uploads

`Integrations\DatabaseUploader::uploadDump()` refuses the dump before any byte leaves your host unless ALL hold:

- SQLite file exists and is readable
- `journal_mode` is `wal` (checked via `PRAGMA journal_mode`)
- `page_size` is `4096`
- `auto_vacuum` is `0`
- `encoding` is UTF-8

If your dump was built differently, the uploader throws `ValidationException` — rebuild the source with:

```sql
PRAGMA journal_mode=WAL;
PRAGMA page_size=4096;
PRAGMA auto_vacuum=0;
PRAGMA encoding='UTF-8';
```

A valid proof dump is included in `docker/upload-proof/php-project/proof.php`.

## Upload host validation

The upload hostname must be a bare host (`dmpupload-….turso.io`) or `https://…` — `http://` is rejected **before** any token is attached to a request. This check also normalizes `https://host` to `host`.

```
ValidationException: Upload hostname must use https, not http, with only https URLs allowed.
```

Use the exact hostname returned by `POST /v1/organizations/{organizationSlug}/databases` when creating the scratch database with `seed.type = 'database_upload'`.

## `Databases::delete()` envelope — scalar-string `database`

Contract fact (pinned OpenAPI snapshot): `DELETE …/databases/{databaseName}` returns `{"database": "<name>"}` where `database` is a **string**, not an object.

- v2.0.0 mistyped the unwrap (`: array`) and threw a `TypeError` — fixed in v2.1.0.
- v2.1.0 returns `string` (the deleted name) or the verbatim array body for future-proofing.

If you pin `darkterminal/turso-api-client-php:^2.0` without an upper bound on 2.x, upgrade to `^2.1` to get the fix.

## v3 Recovery operations require `organizationId`

`Recovery::listDeletedDatabases()`, `restoreDatabase()`, `listDeletedGroups()`, `restoreGroup()` hit `/v3/organizations/{organizationId}/…`. Configure the **UUID**, not the slug:

```php
$client = Client::builder()
    ->withToken($token)
    ->withOrganization('softwarefreestylengineer')   // slug for normal v1/v2 operations
    ->withOrganizationId('0b3f…-…')                   // UUID for v3 recovery
    ->build();
```

Without `withOrganizationId`, recovery operations throw `ConfigurationException`.

## Empty success bodies

Some endpoints (token rotation, config PATCH) return an empty body with 2xx. The client decodes them as `[]` — this is intentional and covered by tests; it is not an error.

## 401/403 — credentials are not printed

On non-2xx the client throws `AuthenticationException` (401/403) with `statusCode`/`body`/`errorCode` from the response — never the bearer token. If you see 401:

- verify the token is a *platform* token (not a database-scoped JWT — that one goes to Hrana, not this SDK)
- personal vs organization-scoped API tokens are different paths (`/v1/auth/api-tokens` vs `/v1/organizations/{organizationSlug}/api-tokens`)
- for 429s, catch `RateLimitException` and read `retryAfter`

## Tests fail on Linux runners: `SQLSTATE[HY000] [14] unable to open database file`

Historical CI failure (before v2.1.0): `tests/IntegrationsTest.php` used hard-coded `/tmp/opencode/pu_test_*.db` paths that do not exist on GitHub Actions runners. Fixed in v2.1.0 — tests now use unique `sys_get_temp_dir()` paths. If you backport tests to an older checkout, replicate `testDumpPath()` before running.

## GitHub Actions workflow rejected on push

Pushing `.github/workflows/*.yml` requires the token/credential to carry `workflow` scope. With `gh`:

```bash
gh auth refresh -h github.com -s workflow
# or with env-token override cleared:
env -u GITHUB_TOKEN gh auth refresh -h github.com -s workflow
```

Local Git over SSH is unaffected. The CI workflow (`tests.yml`) itself runs pest + PHPStan level 6 + composer audit on PHP 8.3/8.4/8.5.

## `docker/upload-proof/` harness

The live upload proof exists to be run from your host, not from GitHub Actions (which does not receive Cloud secrets). Requirements:

- `docker` available on the host
- `TURSO_PLATFORM_API_TOKEN` (live token) in the environment
- optional `TURSO_PLATFORM_ORGANIZATION` (defaults to `softwarefreestylengineer`)

```bash
TURSO_PLATFORM_API_TOKEN=… docker/upload-proof/proof.sh          # matrix 8.3/8.4/8.5
TURSO_PLATFORM_API_TOKEN=… docker/upload-proof/proof.sh 8.4       # single leg
```

The harness creates a scratch database with `seed.type='database_upload'`, uploads a minimal dump, reads the smoke row back over Hrana, and **deletes** the scratch database. If the run aborts before cleanup, remove any `uploadproof-*` scratch databases from the Turso dashboard.

## Region resolution (`Integrations\RegionResolver`)

`closest()` is intentionally unauthenticated: it POSTs to `https://region.turso.io/` and falls back to GET if the POST fails. It throws on network failure — it does not guess a region.

## Cannot reproduce coverage/PHPStan numbers locally

```bash
composer install
composer test     # pest, 78 tests / 167 assertions
composer analyse  # phpstan, level 6
```

PHPStan level 6 requires the in-repo `phpstan.neon` (`treatPhpDocTypesAsCertain: false` is intentional — the SDK validates defensive-ish runtime inputs that PHPDoc narrows away).
