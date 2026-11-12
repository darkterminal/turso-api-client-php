# Architecture

## Namespace tree

```
Darkterminal\TursoPlatformAPI
├── Client                          # public entry point
│   ├── Client.php                  # typed client; `Client::builder()` entry
│   ├── ClientBuilder.php           # immutable builder (token/org/org-id/timeout/transport)
│   └── Configuration.php           # immutable, validated value object
├── Contracts                       # (reserved)
├── Enums
│   ├── Authorization.php           # full-access | read-only
│   ├── ApiTokenScope.php           # 14 contract scopes
│   └── MemberRole.php              # admin | member | viewer
├── Exceptions                      # all extend PlatformException
│   ├── PlatformException.php       # base
│   ├── ConfigurationException.php  # client misconfiguration
│   ├── ValidationException.php     # pre-flight input validation
│   ├── TransportException.php      # network-level failures
│   ├── HttpException.php           # non-2xx base (statusCode/body/errorCode)
│   ├── AuthenticationException.php # 401/403
│   ├── NotFoundException.php       # 404
│   ├── RateLimitException.php      # 429 (+ retryAfter)
│   ├── ServerException.php         # 5xx
│   └── UnexpectedResponseException.php # decode/shape mismatch
├── Resources                       # one class per domain; all operations mapped to OpenAPI operationIds
│   ├── Users.php                   # GET /v1/user
│   ├── Auth.php                    # GET /v1/auth/validate
│   ├── Locations.php               # GET /v1/locations
│   ├── Organizations.php           # 7 operations
│   ├── Members.php                 # 5 operations
│   ├── Invites.php                 # 3 operations (v2)
│   ├── Databases.php               # 12 operations
│   ├── Groups.php                  # 13 operations
│   ├── ApiTokens.php               # 3 operations (personal)
│   ├── OrganizationApiTokens.php   # 2 operations (org-scoped)
│   ├── AuditLogs.php               # 1 operation
│   └── Recovery.php                # 4 operations (v3, requires organizationId)
└── Transport
    └── CurlTransport.php           # PSR-18 default on ext-curl (no follow redirects)
```

## Client pipeline

```
Client::request(method, path, query, body)
  ├─ builds absolute URL from Configuration::baseUrl
  ├─ attaches Bearer token (Authorization header)
  ├─ falls back to CurlTransport when no PSR-18 client is injected
  ├─ maps status → exception hierarchy:
  │    ├─ 401/403 → AuthenticationException
  │    ├─ 404     → NotFoundException
  │    ├─ 429     → RateLimitException (with Retry-After)
  │    ├─ 5xx     → ServerException
  │    └─ other   → HttpException
  └─ empty success bodies are accepted (e.g. No Content)
```

## OpenAPI coverage map

Contract pinned at `openapi/snapshots/openapi-3.0.1-v0.1.0-328d56b8a9ea.json` (SHA-256 in `openapi/coverage-manifest.yaml`).
Every operationId is assigned to exactly one resource; see `docs/api-map.md` for the full list.
