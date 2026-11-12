# API Map — `darkterminal/turso-api-client-php` v2

Convention: each operation is identified by its OpenAPI `operationId` from the pinned snapshot. Coverage status refers to that snapshot; anything outside it is unsupported and will throw a configuration or validation exception.

## Users

| Operation | Method/Path | Method |
|---|---|---|
| getCurrentUser | GET /v1/user | `user()->get()` |

## Auth

| Operation | Method/Path | Method |
|---|---|---|
| validateAPIToken | GET /v1/auth/validate | `auth()->validate()` |

## Locations

| Operation | Method/Path | Method |
|---|---|---|
| listLocations | GET /v1/locations | `locations()->list()` |

## Organizations

| Operation | Method/Path | Method |
|---|---|---|
| listOrganizations | GET /v1/organizations | `organizations()->list()` |
| getOrganization | GET /v1/organizations/{organizationSlug} | `organizations()->get()` |
| updateOrganization | PATCH /v1/organizations/{organizationSlug} | `organizations()->update(array $settings)` |
| listOrganizationPlans | GET …/plans | `organizations()->listPlans()` |
| listOrganizationInvoices | GET …/invoices?type=all|upcoming|issued | `organizations()->listInvoices(string $type = 'all')` |
| getOrganizationSubscription | GET …/subscription | `organizations()->getSubscription()` |
| getOrganizationUsage | GET …/usage | `organizations()->getUsage()` |

## Members

| Operation | Method/Path | Method |
|---|---|---|
| listOrganizationMembers | GET …/members | `members()->list()` |
| addOrganizationMember | POST …/members | `members()->add(?string $email, ?string $username, MemberRole $role = MemberRole::MEMBER)` |
| getOrganizationMember | GET …/members/{username} | `members()->get(string $username)` |
| updateMemberRole | PATCH …/members/{username} | `members()->updateRole(string $username, MemberRole $role)` |
| removeOrganizationMember | DELETE …/members/{username} | `members()->remove(string $username)` |

## Invites (v2)

| Operation | Method/Path | Method |
|---|---|---|
| listOrganizationInvitesV2 | GET /v2/organizations/{organizationSlug}/invites | `invites()->list()` |
| inviteOrganizationMemberV2 | POST /v2/organizations/{organizationSlug}/invites | `invites()->create(?string $email, ?string $username, MemberRole $role = MemberRole::MEMBER)` |
| deleteOrganizationInviteByEmailV2 | DELETE …/invites/{email} | `invites()->delete(string $email)` |

## Databases

| Operation | Method/Path | Method |
|---|---|---|
| listDatabases | GET …/databases?group=&schema=&parent= | `databases()->list(?string $group = null, ?string $schema = null, ?string $parent = null)` |
| createDatabase | POST …/databases | `databases()->create(string $name, string $group, ?array $seed = null, ?string $sizeLimit = null, ?array $remoteEncryption = null)` |
| getDatabase | GET …/databases/{databaseName} | `databases()->get(string $databaseName)` |
| deleteDatabase | DELETE …/databases/{databaseName} | `databases()->delete(string $databaseName)` |
| getDatabaseConfiguration | GET …/databases/{databaseName}/configuration | `databases()->getConfiguration(string $databaseName)` |
| updateDatabaseConfiguration | PATCH …/databases/{databaseName}/configuration | `databases()->updateConfiguration(string $databaseName, array $configuration)` |
| listDatabaseInstances | GET …/databases/{databaseName}/instances | `databases()->listInstances(string $databaseName)` |
| getDatabaseInstance | GET …/databases/{databaseName}/instances/{instanceName} | `databases()->getInstance(string $databaseName, string $instanceName)` |
| createDatabaseToken | POST …/databases/{databaseName}/auth/tokens?expiration=&authorization= | `databases()->createToken(string $databaseName, string $expiration = 'never', Authorization $authorization = Authorization::FULL_ACCESS, array $attachDatabases = [])` |
| invalidateDatabaseTokens | POST …/databases/{databaseName}/auth/rotate | `databases()->invalidateTokens(string $databaseName)` |
| getDatabaseUsage | GET …/databases/{databaseName}/usage?from=&to= | `databases()->usage(string $databaseName, ?string $from = null, ?string $to = null)` |
| getDatabaseStats | GET …/databases/{databaseName}/stats | `databases()->stats(string $databaseName)` |

## Groups

| Operation | Method/Path | Method |
|---|---|---|
| listGroups | GET …/groups | `groups()->list()` |
| createGroup | POST …/groups | `groups()->create(string $groupName, string $location, string\|array\|null $extensions = null)` |
| getGroup | GET …/groups/{groupName} | `groups()->get(string $groupName)` |
| deleteGroup | DELETE …/groups/{groupName} | `groups()->delete(string $groupName)` |
| getGroupConfiguration | GET …/groups/{groupName}/configuration | `groups()->getConfiguration(string $groupName)` |
| updateGroupConfiguration | PATCH …/groups/{groupName}/configuration | `groups()->updateConfiguration(string $groupName, array $configuration)` |
| transferGroup | POST …/groups/{groupName}/transfer | `groups()->transfer(string $groupName, string $targetOrganization)` |
| unarchiveGroup | POST …/groups/{groupName}/unarchive | `groups()->unarchive(string $groupName)` |
| addLocationToGroup | POST …/groups/{groupName}/locations/{location} | `groups()->addLocation(string $groupName, string $location)` |
| removeLocationFromGroup | DELETE …/groups/{groupName}/locations/{location} | `groups()->removeLocation(string $groupName, string $location)` |
| updateGroupDatabases | POST …/groups/{groupName}/update | `groups()->update(string $groupName)` |
| createGroupToken | POST …/groups/{groupName}/auth/tokens?expiration=&authorization= | `groups()->createToken(string $groupName, string $expiration = 'never', Authorization $authorization = Authorization::FULL_ACCESS, array $attachDatabases = [])` |
| invalidateGroupTokens | POST …/groups/{groupName}/auth/rotate | `groups()->invalidateTokens(string $groupName)` |

## API Tokens — personal

| Operation | Method/Path | Method |
|---|---|---|
| listAPITokens | GET /v1/auth/api-tokens | `apiTokens()->list()` |
| createAPIToken | POST /v1/auth/api-tokens/{tokenName} | `apiTokens()->create(string $tokenName, ?string $organization = null, ?string $group = null, ?array $scopes = null)` |
| revokeAPIToken | DELETE /v1/auth/api-tokens/{tokenName} | `apiTokens()->revoke(string $tokenName)` |

## API Tokens — organization-scoped

| Operation | Method/Path | Method |
|---|---|---|
| listOrganizationAPITokens | GET /v1/organizations/{organizationSlug}/api-tokens | `organizationApiTokens()->list()` |
| revokeOrganizationAPIToken | DELETE /v1/organizations/{organizationSlug}/api-tokens/{tokenId} | `organizationApiTokens()->revoke(string $tokenId)` |

## Recovery (v3)

Requires `withOrganizationId(string $organizationId)` on the builder.

| Operation | Method/Path | Method |
|---|---|---|
| listDeletedDatabases | GET /v3/organizations/{organizationId}/databases?deleted=true&search=&limit=&cursor= | `recovery()->listDeletedDatabases(?string $search = null, int $limit = 20, ?string $cursor = null)` |
| restoreDatabase | POST /v3/organizations/{organizationId}/databases/{databaseId}/restore | `recovery()->restoreDatabase(string $databaseId, ?string $name = null)` |
| listDeletedGroups | GET /v3/organizations/{organizationId}/deleted-groups?limit=&cursor= | `recovery()->listDeletedGroups(int $limit = 20, ?string $cursor = null)` |
| restoreGroup | POST /v3/organizations/{organizationId}/groups/{groupId}/restore | `recovery()->restoreGroup(string $groupId)` |

## Audit logs

| Operation | Method/Path | Method |
|---|---|---|
| listOrganizationAuditLogs | GET /v1/organizations/{organizationSlug}/audit-logs?page=&page_size= | `auditLogs()->list(int $page = 1, int $pageSize = 25)` |
File not found: 


## Contract quirks observed in the snapshot and verified live

| Operation | Quirk | Handling |
|---|---|---|
| deleteDatabase (DELETE …/databases/{databaseName}) | response envelope `{"database": "<string-name>"}` — `database` is a scalar string, not an object | `delete()` returns `string` when present, otherwise the verbatim array (typed `string|array` since v2.1.0) |
| Token rotation / config PATCH | empty 2xx body | decoded as `[]` by the client (not an error) |
| v3 Recovery operations | `/v3/organizations/{organizationId}/…` — organizationId is a **UUID**, not the slug | requires `withOrganizationId('…')`; otherwise `ConfigurationException` |
| createDatabaseToken / createGroupToken | `expiration`/`authorization` travel as query parameters; `attachDatabases` travel as JSON body `permissions.read_attach.databases` | returned as raw JWT `string` |
| Region resolution (not an OpenAPI operation) | no auth required | see `Integrations\RegionResolver` (POST, GET fallback); standalone from the SDK pipeline |

Envelope fields not listed in the snapshot are preserved end-to-end (forward compatibility). Unknown future operations are rejected by construction — they do not silently fall through to an HTTP call.
