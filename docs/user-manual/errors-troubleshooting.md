---
title: Errors and troubleshooting
description: Exception hierarchy, error handling patterns, and real troubleshooting notes from the SDK contract.
section: Reference
eyebrow: Reference
---

## Exception hierarchy

All SDK exceptions extend `Darkterminal\TursoPlatformAPI\Exceptions\PlatformException`.

| Exception | When it happens |
|---|---|
| `ConfigurationException` | Invalid builder/configuration, missing org slug/ID required by a resource. |
| `ValidationException` | Local input validation failed before sending request. |
| `TransportException` | Network/PSR-18 transport failure. |
| `HttpException` | Non-2xx HTTP response base class. |
| `AuthenticationException` | 401/403. |
| `NotFoundException` | 404. |
| `RateLimitException` | 429; may expose retry-after. |
| `ServerException` | 5xx. |
| `UnexpectedResponseException` | Response decode/shape mismatch. |

## Handling pattern

```php
use Darkterminal\TursoPlatformAPI\Exceptions\AuthenticationException;
use Darkterminal\TursoPlatformAPI\Exceptions\NotFoundException;
use Darkterminal\TursoPlatformAPI\Exceptions\PlatformException;
use Darkterminal\TursoPlatformAPI\Exceptions\RateLimitException;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;

try {
    $client->databases()->get('app-db');
} catch (ValidationException|AuthenticationException|NotFoundException|RateLimitException $e) {
    // Handle expected cases explicitly.
} catch (PlatformException $e) {
    // Fallback for transport/server/unexpected SDK errors.
}
```

## Troubleshooting

### 401/403 authentication failures

Symptoms: `AuthenticationException`.

Checks:

- Confirm you are using a **Platform API token**, not a database JWT.
- Personal and organization API tokens have different scopes/capabilities.
- Do not log raw token values. The SDK avoids printing credentials in exception messages.

### Missing organization slug

Symptoms: `ConfigurationException` when calling org-scoped resources.

Fix:

```php
$client = Client::builder()
    ->withToken($token)
    ->withOrganization('acme')
    ->build();
```

### Missing organization ID for recovery

Symptoms: recovery methods fail with `ConfigurationException`.

Fix:

```php
$client = Client::builder()
    ->withToken($token)
    ->withOrganization('acme')
    ->withOrganizationId('uuid-here')
    ->build();
```

`withOrganizationId()` takes the organization UUID used by `/v3/organizations/{organizationId}/...`.

### Database delete return type

`databases()->delete()` may return a string name or a decoded array because the contract envelope can be scalar. Do not assume object shape.

```php
$result = $client->databases()->delete('app-db');
if (is_string($result)) { /* deleted name */ }
```

### Empty 2xx bodies

Some endpoints return 2xx with no body. The SDK treats this as `[]`. This is expected for token rotation/config operations.

### Upload preflight failure

`DatabaseUploader` validates the local SQLite file before upload. Ensure:

```sql
PRAGMA journal_mode=WAL;
PRAGMA page_size=4096;
PRAGMA auto_vacuum=0;
PRAGMA encoding='UTF-8';
```

Also ensure upload host uses HTTPS. HTTP is rejected before credentials are attached.

### 429 rate limit

Catch `RateLimitException` and respect `Retry-After` when present. Add backoff/jitter in automation jobs.

### PHPStan/Pest local checks

From repository root:

```bash
composer install
composer test
composer analyse
```

CI runs Pest and PHPStan level 6 on PHP 8.3/8.4/8.5.