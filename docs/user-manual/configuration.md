---
title: Configuration
description: Configure the ClientBuilder, transport, base URL, timeout, organization slug, and organization ID.
section: Guide
eyebrow: Configuration
---

## Builder

`Client` is created through `Client::builder()`:

```php
use Darkterminal\TursoPlatformAPI\Client\Client;

$client = Client::builder()
    ->withToken($_ENV['TURSO_PLATFORM_API_TOKEN'])
    ->withOrganization('acme')
    ->withOrganizationId('00000000-0000-0000-0000-000000000000')
    ->withBaseUrl('https://api.turso.tech')
    ->withTimeout(20)
    ->build();
```

`ClientBuilder` is immutable. Each `with*()` call returns a cloned builder.

## Options

| Method | Type | Default | Purpose |
|---|---|---|---|
| `withToken(string $token)` | string | required | Platform API Bearer token. |
| `withOrganization(string $organization)` | string | `null` | Default organization slug for org-scoped v1/v2 resources. |
| `withOrganizationId(string $organizationId)` | string | `null` | Organization UUID for v3 recovery endpoints. |
| `withBaseUrl(string $baseUrl)` | string | `https://api.turso.tech` | Platform API base URL. Must be `http` or `https`. |
| `withTimeout(int $timeout)` | int seconds | `20` | Transport timeout. Must be >= 1. |
| `withTransport(ClientInterface $transport)` | PSR-18 client | `CurlTransport` | Custom HTTP transport. |
| `build()` | — | — | Returns `Client` with validated `Configuration`. |

## Configuration validation

`Configuration` rejects invalid values with `ConfigurationException`:

- Empty token.
- Empty organization/organizationId when provided.
- Timeout less than 1 second.
- Base URL without http/https scheme or host.

## Organization slug vs organization ID

This distinction matters:

- **Organization slug** (`withOrganization`) is used by most v1/v2 resources, e.g. `/v1/organizations/{organizationSlug}/databases`.
- **Organization ID** (`withOrganizationId`) is a UUID required by v3 recovery endpoints, e.g. `/v3/organizations/{organizationId}/databases?...&deleted=true`.

You can set both:

```php
$client = Client::builder()
    ->withToken($token)
    ->withOrganization('acme')
    ->withOrganizationId('f1d2...uuid...')
    ->build();
```

If a recovery method is called without an organization ID, the SDK throws `ConfigurationException` before making a request.

## Custom PSR-18 transport

Inject any PSR-18 client:

```php
use Darkterminal\TursoPlatformAPI\Client\Client;
use Psr\Http\Client\ClientInterface;

$psr18 = new MyPsr18Client();

$client = Client::builder()
    ->withToken($_ENV['TURSO_PLATFORM_API_TOKEN'])
    ->withTransport($psr18)
    ->build();
```

When using a custom transport, you are responsible for its timeout, redirect, TLS, and proxy behavior. The SDK's default `CurlTransport` does not follow redirects.

## Base URL overrides

The base URL can be changed for testing or proxies:

```php
$client = Client::builder()
    ->withToken($token)
    ->withBaseUrl('https://api.turso.tech')
    ->build();
```

Use HTTPS in production. Do not point the SDK at untrusted endpoints with real tokens.

## Resource access

`Client` exposes resource getters:

```php
$client->user();
$client->auth();
$client->locations();
$client->organizations();
$client->members();
$client->invites();
$client->databases();
$client->groups();
$client->apiTokens();
$client->organizationApiTokens();
$client->auditLogs();
$client->recovery();
```

Each getter returns a resource instance bound to the same client configuration.