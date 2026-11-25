---
title: Client reference
description: Reference for Client, ClientBuilder, Configuration, and resource accessors.
section: Reference
eyebrow: Reference
---

## Client

Namespace: `Darkterminal\TursoPlatformAPI\Client\Client`

### Builder entry

```php
$client = Client::builder()
    ->withToken($token)
    ->build();
```

### Resource accessors

| Method | Returns |
|---|---|
| `user()` | `Resources\Users` |
| `auth()` | `Resources\Auth` |
| `locations()` | `Resources\Locations` |
| `organizations()` | `Resources\Organizations` |
| `members()` | `Resources\Members` |
| `invites()` | `Resources\Invites` |
| `databases()` | `Resources\Databases` |
| `groups()` | `Resources\Groups` |
| `apiTokens()` | `Resources\ApiTokens` |
| `organizationApiTokens()` | `Resources\OrganizationApiTokens` |
| `auditLogs()` | `Resources\AuditLogs` |
| `recovery()` | `Resources\Recovery` |

## ClientBuilder

Namespace: `Darkterminal\TursoPlatformAPI\Client\ClientBuilder`

| Method | Description |
|---|---|
| `withToken(string $token): self` | Set Platform API token. |
| `withOrganization(string $organization): self` | Set default organization slug. |
| `withOrganizationId(string $organizationId): self` | Set organization UUID for v3 recovery. |
| `withBaseUrl(string $baseUrl): self` | Set API base URL. Default `https://api.turso.tech`. |
| `withTimeout(int $timeout): self` | Set timeout seconds. Default `20`. |
| `withTransport(Psr\Http\Client\ClientInterface $transport): self` | Inject PSR-18 transport. |
| `build(): Client` | Build validated client. |

Builder is immutable; each setter clones.

## Configuration

Namespace: `Darkterminal\TursoPlatformAPI\Client\Configuration`

### Constants

| Constant | Value |
|---|---|
| `DEFAULT_BASE_URL` | `https://api.turso.tech` |
| `DEFAULT_TIMEOUT` | `20` |

### Constructor properties

| Property | Type |
|---|---|
| `token` | `string` |
| `organization` | `?string` |
| `organizationId` | `?string` |
| `baseUrl` | `string` |
| `timeout` | `int` |
| `transport` | `?Psr\Http\Client\ClientInterface` |

### Validation rules

- Token cannot be empty.
- Organization/org ID cannot be empty strings when provided.
- Timeout must be >= 1.
- Base URL must be http/https with host.

## Request behavior

`Client::request()` (internal/public depending on SDK surface) handles:

- URL construction from `baseUrl`.
- Bearer token authorization header.
- PSR-18 transport dispatch or default `CurlTransport`.
- Status-to-exception mapping.
- JSON decoding and empty-body normalization.

Resource classes use this pipeline; callers should usually use resource methods rather than raw requests.