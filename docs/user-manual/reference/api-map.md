---
title: API map
description: Mapped Turso Platform API operations and their SDK methods.
section: Reference
eyebrow: Reference
toc: false
---

## Coverage source

This map is derived from `openapi/coverage-manifest.yaml` and the SDK resource methods. The pinned OpenAPI snapshot is the contract source; SDK methods are only considered supported when mapped and tested.

## Operation map

| Resource | OperationId | HTTP | Path | SDK method |
|---|---|---|---|---|
| apiTokens | revokeAPIToken | DELETE | `/v1/auth/api-tokens/{tokenName}` | `apiTokens()->revoke()` |
| apiTokens | listAPITokens | GET | `/v1/auth/api-tokens` | `apiTokens()->list()` |
| apiTokens | createAPIToken | POST | `/v1/auth/api-tokens/{tokenName}` | `apiTokens()->create()` |
| auditLogs | listOrganizationAuditLogs | GET | `/v1/organizations/{organizationSlug}/audit-logs` | `auditLogs()->list()` |
| auth | validateAPIToken | GET | `/v1/auth/validate` | `auth()->validate()` |
| databases | deleteDatabase | DELETE | `/v1/organizations/{organizationSlug}/databases/{databaseName}` | `databases()->delete()` |
| databases | listDatabases | GET | `/v1/organizations/{organizationSlug}/databases` | `databases()->list()` |
| databases | getDatabase | GET | `/v1/organizations/{organizationSlug}/databases/{databaseName}` | `databases()->get()` |
| databases | getDatabaseConfiguration | GET | `/v1/organizations/{organizationSlug}/databases/{databaseName}/configuration` | `databases()->getConfiguration()` |
| databases | listDatabaseInstances | GET | `/v1/organizations/{organizationSlug}/databases/{databaseName}/instances` | `databases()->listInstances()` |
| databases | getDatabaseInstance | GET | `/v1/organizations/{organizationSlug}/databases/{databaseName}/instances/{instanceName}` | `databases()->getInstance()` |
| databases | getDatabaseStats | GET | `/v1/organizations/{organizationSlug}/databases/{databaseName}/stats` | `databases()->stats()` |
| databases | getDatabaseUsage | GET | `/v1/organizations/{organizationSlug}/databases/{databaseName}/usage` | `databases()->usage()` |
| databases | updateDatabaseConfiguration | PATCH | `/v1/organizations/{organizationSlug}/databases/{databaseName}/configuration` | `databases()->updateConfiguration()` |
| databases | createDatabase | POST | `/v1/organizations/{organizationSlug}/databases` | `databases()->create()` |
| databases | invalidateDatabaseTokens | POST | `/v1/organizations/{organizationSlug}/databases/{databaseName}/auth/rotate` | `databases()->invalidateTokens()` |
| databases | createDatabaseToken | POST | `/v1/organizations/{organizationSlug}/databases/{databaseName}/auth/tokens` | `databases()->createToken()` |
| groups | deleteGroup | DELETE | `/v1/organizations/{organizationSlug}/groups/{groupName}` | `groups()->delete()` |
| groups | removeLocationFromGroup | DELETE | `/v1/organizations/{organizationSlug}/groups/{groupName}/locations/{location}` | `groups()->removeLocation()` |
| groups | listGroups | GET | `/v1/organizations/{organizationSlug}/groups` | `groups()->list()` |
| groups | getGroup | GET | `/v1/organizations/{organizationSlug}/groups/{groupName}` | `groups()->get()` |
| groups | getGroupConfiguration | GET | `/v1/organizations/{organizationSlug}/groups/{groupName}/configuration` | `groups()->getConfiguration()` |
| groups | updateGroupConfiguration | PATCH | `/v1/organizations/{organizationSlug}/groups/{groupName}/configuration` | `groups()->updateConfiguration()` |
| groups | addLocationToGroup | POST | `/v1/organizations/{organizationSlug}/groups/{groupName}/locations/{location}` | `groups()->addLocation()` |
| groups | transferGroup | POST | `/v1/organizations/{organizationSlug}/groups/{groupName}/transfer` | `groups()->transfer()` |
| groups | unarchiveGroup | POST | `/v1/organizations/{organizationSlug}/groups/{groupName}/unarchive` | `groups()->unarchive()` |
| groups | updateGroupDatabases | POST | `/v1/organizations/{organizationSlug}/groups/{groupName}/update` | `groups()->update()` |
| groups | invalidateGroupTokens | POST | `/v1/organizations/{organizationSlug}/groups/{groupName}/auth/rotate` | `groups()->invalidateTokens()` |
| groups | createGroupToken | POST | `/v1/organizations/{organizationSlug}/groups/{groupName}/auth/tokens` | `groups()->createToken()` |
| groups | createGroup | POST | `/v1/organizations/{organizationSlug}/groups` | `groups()->create()` |
| invites | listOrganizationInvitesV2 | GET | `/v2/organizations/{organizationSlug}/invites` | `invites()->list()` |
| invites | inviteOrganizationMemberV2 | POST | `/v2/organizations/{organizationSlug}/invites` | `invites()->create()` |
| invites | deleteOrganizationInviteByEmailV2 | DELETE | `/v2/organizations/{organizationSlug}/invites/{email}` | `invites()->delete()` |
| locations | listLocations | GET | `/v1/locations` | `locations()->list()` |
| members | listOrganizationMembers | GET | `/v1/organizations/{organizationSlug}/members` | `members()->list()` |
| members | addOrganizationMember | POST | `/v1/organizations/{organizationSlug}/members` | `members()->add()` |
| members | getOrganizationMember | GET | `/v1/organizations/{organizationSlug}/members/{username}` | `members()->get()` |
| members | updateMemberRole | PATCH | `/v1/organizations/{organizationSlug}/members/{username}` | `members()->updateRole()` |
| members | removeOrganizationMember | DELETE | `/v1/organizations/{organizationSlug}/members/{username}` | `members()->remove()` |
| organizationApiTokens | listOrganizationAPITokens | GET | `/v1/organizations/{organizationSlug}/api-tokens` | `organizationApiTokens()->list()` |
| organizationApiTokens | revokeOrganizationAPIToken | DELETE | `/v1/organizations/{organizationSlug}/api-tokens/{tokenId}` | `organizationApiTokens()->revoke()` |
| organizations | listOrganizations | GET | `/v1/organizations` | `organizations()->list()` |
| organizations | getOrganization | GET | `/v1/organizations/{organizationSlug}` | `organizations()->get()` |
| organizations | updateOrganization | PATCH | `/v1/organizations/{organizationSlug}` | `organizations()->update()` |
| organizations | listOrganizationPlans | GET | `/v1/organizations/{organizationSlug}/plans` | `organizations()->listPlans()` |
| organizations | listOrganizationInvoices | GET | `/v1/organizations/{organizationSlug}/invoices` | `organizations()->listInvoices()` |
| organizations | getOrganizationSubscription | GET | `/v1/organizations/{organizationSlug}/subscription` | `organizations()->getSubscription()` |
| organizations | getOrganizationUsage | GET | `/v1/organizations/{organizationSlug}/usage` | `organizations()->getUsage()` |
| recovery | listDeletedDatabases | GET | `/v3/organizations/{organizationId}/databases` | `recovery()->listDeletedDatabases()` |
| recovery | restoreDatabase | POST | `/v3/organizations/{organizationId}/databases/{databaseId}/restore` | `recovery()->restoreDatabase()` |
| recovery | listDeletedGroups | GET | `/v3/organizations/{organizationId}/deleted-groups` | `recovery()->listDeletedGroups()` |
| recovery | restoreGroup | POST | `/v3/organizations/{organizationId}/groups/{groupId}/restore` | `recovery()->restoreGroup()` |
| users | getCurrentUser | GET | `/v1/user` | `user()->get()` |

## Notes

- `recovery` methods require `withOrganizationId()` (UUID), not slug.
- `deleteDatabase` may return string or array depending on contract envelope.
- Some 2xx responses have empty bodies and are normalized to `[]`.