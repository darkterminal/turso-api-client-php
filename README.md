<p align="center">
  <a href="https://discord.gg/turso">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://i.imgur.com/UhuW3zm.png">
      <source media="(prefers-color-scheme: light)" srcset="https://i.imgur.com/vljWbfr.png">
      <img alt="Shows a black logo in light color mode and a white one in dark color mode." src="https://i.imgur.com/vGCC0I4.png">
    </picture>
  </a>
</p>

<p align="center">
  <img alt="Turso Platform API" src="https://github.com/darkterminal.png?size=128" width="96">
</p>

# Turso Platform PHP SDK

Typed, resource-oriented PHP SDK for the **Turso Platform API** (`https://api.turso.tech`).

Framework-independent core, one composer package, no Laravel lock-in — Laravel users get a separate adapter package (planned as a follow-up).

**Status**: spec-conformant work in progress. All 53 operations from the pinned OpenAPI snapshot are implemented and tested, but this API is still in `2.0.0-dev` until the v2 release is tagged.

## Requirements

| Component | Version |
|---|---|
| PHP | 8.3+ |
| ext-curl | required |
| ext-json | required |
| PSR-18 HTTP client | optional — SDK ships a reasonable default (`CurlTransport`) |

## Installation

```bash
composer require darkterminal/turso-api-client-php
```

## Quick start

```php
use Darkterminal\TursoPlatformAPI\Client\Client;

$client = Client::builder()
    ->withToken(getenv('TURSO_API_TOKEN'))
    ->withOrganization('acme')            // slug, for organization-scoped resources
    ->build();

$databases = $client->databases()->list();
$me        = $client->user()->get();
```

### Configuration

| Method | Purpose |
|---|---|
| `withToken(string)` | Bearer token sent as `Authorization: Bearer …` |
| `withOrganization(string)` | Organization slug (e.g. `'acme'`) — needed for databases/groups/members/etc. |
| `withOrganizationId(string)` | Organization ID (**UUID**) — needed for the v3 recovery operations (`listDeletedDatabases`, `restoreDatabase`, `listDeletedGroups`, `restoreGroup`) |
| `withBaseUrl(string)` | Custom base URL (default `https://api.turso.tech`) |
| `withTimeout(int)` | cURL timeout in seconds (default `20`) |
| `withTransport(Psr\Http\Client\ClientInterface)` | Inject any PSR-18 client (e.g. Guzzle) |

## Resources

Every resource maps to the pinned OpenAPI `operationId`.

| Resource | Operations |
|---|---|
| `user()` | `user()->get()` — `GET /v1/user` |
| `auth()` | `auth()->validate()` — `GET /v1/auth/validate` |
| `locations()` | `locations()->list()` — `GET /v1/locations` |
| `organizations()` | list, get, update, listPlans, listInvoices, getSubscription, getUsage |
| `members()` | list, add (email or username), get, updateRole, remove |
| `invites()` | list, create (exactly one of email\|username), delete (v2) |
| `databases()` | list, create, get, delete, get/update configuration, list/get instances, createToken, invalidateTokens, usage, stats |
| `groups()` | list, create, get, delete, get/update configuration, transfer, unarchive, add/remove location, update, createToken, invalidateTokens |
| `apiTokens()` | list, create, revoke (personal) |
| `organizationApiTokens()` | list, revoke (organization-scoped) |
| `recovery()` | listDeletedDatabases, restoreDatabase, listDeletedGroups, restoreGroup (v3, requires `withOrganizationId()`) |
| `auditLogs()` | list (page / page_size) |

See [docs/api-map.md](docs/api-map.md) for the full operation inventory and contract coverage map.

## Error handling

| HTTP status | Exception |
|---|---|
| Network/transport failure | `TransportException` |
| 401 / 403 | `AuthenticationException` |
| 404 | `NotFoundException` |
| 429 | `RateLimitException` (with `->retryAfter` when the server supplies it) |
| 5xx | `ServerException` |
| Other non-2xx | `HttpException` |
| Validation (pre-flight) | `ValidationException` |
| Config (missing token/organizationId/…) | `ConfigurationException` |

Credentials are never reflected in exception messages, bodies, or logs; network-level failures throw a transport-only `TransportException`.

## Testing

```bash
composer install
./vendor/bin/pest
```

63 tests / 143 assertions cover all resources (via an in-memory PSR-18 double).

## What is **not** supported yet

- **Upload/dump** (`database_upload` seed in OpenAPI is intentionally not wired — the upload contract is a separate integration and will be gated/verified before shipping).
- **Region resolver** (`https://region.turso.io`) — separate contract, not implemented.
- **Laravel adapter** — planned as a follow-up package; this package is Laravel-free.
- **Offline/journaled-write modes** — out of scope for an HTTP Platform API client.

## Versioning

- Semantic Versioning. Migration guide lives in [CHANGELOG.md](CHANGELOG.md).
- Public API of v2.x is the typed client built via `Client::builder()`.
- v1.x API was removed entirely in v2.0.0 — see the migration notes.

## Security

- Bearer token is attached only after validating the configured base URL and request path; the default `CurlTransport` does not follow redirects, so it cannot leak credentials across host boundaries.
- No endpoint is called with the wrong transport: organization-scoped resources throw `ConfigurationException` when no organization is configured; v3 recovery operations throw when no `organizationId` is configured.
- Secrets never appear in logs, exceptions, or error bodies (see tests: “never leaks the token in exception messages”).

## Documentation

- [v2 API map](docs/api-map.md)
- [Architecture notes](docs/architecture.md)
- [CHANGELOG](CHANGELOG.md)

## Contributing & Security

Please see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities privately via [GitHub Security Advisories](https://github.com/darkterminal/turso-api-client-php/security/advisories/new).

## License

MIT — see [LICENSE](LICENSE).
