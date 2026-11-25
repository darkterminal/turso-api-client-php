---
title: Groups
description: Manage Turso groups, locations, configuration, transfer, unarchive, update, and group tokens.
section: Resources
eyebrow: Resources
---

## Overview

`Groups` manages logical database groups in a Turso organization. Groups control location placement and group-level configuration/tokens.

## Methods

| Method | Contract operation | Notes |
|---|---|---|
| `list(): array` | `listGroups` | List groups. |
| `create(string $groupName, string $location, string|array|null $extensions = null): array` | `createGroup` | Create group in primary location. |
| `get(string $groupName): array` | `getGroup` | Get group details. |
| `delete(string $groupName): array` | `deleteGroup` | Delete group. |
| `getConfiguration(string $groupName): array` | `getGroupConfiguration` | Read group config. |
| `updateConfiguration(string $groupName, array $configuration): array` | `updateGroupConfiguration` | Update allowed config keys. |
| `transfer(string $groupName, string $targetOrganization): array` | `transferGroup` | Transfer to another org. |
| `unarchive(string $groupName): array` | `unarchiveGroup` | Unarchive group. |
| `addLocation(string $groupName, string $location): array` | `addLocationToGroup` | Add location. |
| `removeLocation(string $groupName, string $location): array` | `removeLocationFromGroup` | Remove location. |
| `update(string $groupName): array` | `updateGroupDatabases` | Trigger group database update/version operation. |
| `createToken(string $groupName, string $expiration = 'never', Authorization $authorization = Authorization::FULL_ACCESS, array $attachDatabases = []): array` | `createGroupToken` | Create group token. |
| `invalidateTokens(string $groupName): array` | `invalidateGroupTokens` | Rotate/invalidate tokens. |

## Create a group

```php
$group = $client->groups()->create(
    groupName: 'default',
    location: 'aws-us-east-1',
);
```

With extensions:

```php
$group = $client->groups()->create(
    groupName: 'analytics',
    location: 'aws-us-east-1',
    extensions: ['vector'],
);
```

Allowed extension values are validated by the SDK:

`vector`, `crypto`, `fuzzy`, `math`, `stats`, `text`, `unicode`, `uuid`, `regexp`, `vec`.

Invalid extensions throw `ValidationException`.

## Locations

```php
$client->groups()->addLocation('default', 'aws-eu-west-1');
$client->groups()->removeLocation('default', 'aws-eu-west-1');
```

Use `locations()->list()` or the RegionResolver integration to select valid locations.

## Configuration

```php
$config = $client->groups()->getConfiguration('default');

$client->groups()->updateConfiguration('default', [
    'delete_protection' => true,
]);
```

Only allowed group configuration keys are accepted by the SDK. Unknown keys throw `ValidationException`.

## Tokens

```php
use Darkterminal\TursoPlatformAPI\Enums\Authorization;

$token = $client->groups()->createToken(
    groupName: 'default',
    expiration: 'never',
    authorization: Authorization::FULL_ACCESS,
);

$jwt = $token['jwt'];
```

Invalidate/rotate group tokens:

```php
$client->groups()->invalidateTokens('default');
```

## Transfer and unarchive

```php
$client->groups()->transfer('default', 'target-org-slug');
$client->groups()->unarchive('default');
```

These operations can have significant platform effects. Use them with explicit operator intent and audit logging.