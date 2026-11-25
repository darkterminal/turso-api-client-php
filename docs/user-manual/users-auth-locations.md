---
title: Users, auth, and locations
description: Read the current user, validate Platform API tokens, and list Turso locations.
section: Resources
eyebrow: Resources
---

## Users

`Users` maps to `GET /v1/user` (`operationId: getCurrentUser`).

```php
$user = $client->user()->get();
```

### `get(): array`

Returns the decoded response body from the Platform API. The envelope is preserved as-is.

Typical fields include user identity metadata as defined by the current Platform API contract.

## Auth

`Auth` maps to `GET /v1/auth/validate` (`operationId: validateAPIToken`).

```php
$result = $client->auth()->validate();
```

### `validate(): array`

Validates the configured Platform API token.

Expected envelope:

```php
['exp' => 1735689600]
```

Use this for startup checks, diagnostics, or administrative tooling. Do not call it on every request path unless necessary; it is still an HTTP call.

## Locations

`Locations` maps to `GET /v1/locations` (`operationId: listLocations`).

```php
$locations = $client->locations()->list();
```

### `list(): array`

Returns the available Turso locations payload as decoded JSON.

This is useful when creating groups/databases and you want to present valid location choices. For latency-based selection, also consider `Integrations\RegionResolver`.

## Errors

These resources can throw the standard SDK exceptions:

- `ConfigurationException` — invalid client configuration.
- `AuthenticationException` — invalid/expired token, or forbidden.
- `RateLimitException` — platform rate limit.
- `ServerException` — 5xx platform error.
- `TransportException` — network/transport failure.
- `UnexpectedResponseException` — response decode/shape mismatch where applicable.