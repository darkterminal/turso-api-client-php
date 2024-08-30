# Groups

Create and manage where your database is located.

## List Groups

Returns a list of groups belonging to the organization or user.

```php
$turso->groups()->list()->get();
```

Ref: https://docs.turso.tech/api-reference/groups/list

## Create Group

Creates a new group for the organization or user.

```php
use Darkterminal\TursoPlatformAPI\core\Enums\Location;
use Darkterminal\TursoPlatformAPI\core\Enums\Extension;

// Create group with default closest region and all extension enables
$turso->groups()->create('punk')->get();
// Create group with selected region and all extension enables
$turso->groups()->create('punk', Location::AMS)->get();
// Create group with selected region and selected extensions enables
$turso->groups()->create('punk', Location::AMS, ->get()[
    Extension::MATH,
    Extension::TEXT
]);
```

Ref: https://docs.turso.tech/api-reference/groups/create

## Retrieve Group

Returns a group belonging to the organization or user.

```php
$turso->groups()->getGroup('punk')->get();
```

Ref: https://docs.turso.tech/api-reference/groups/retrieve

## Delete Group

Delete a group belonging to the organization or user.

```php
$turso->groups()->delete('punk')->get();
```

Ref: https://docs.turso.tech/api-reference/groups/delete

## Add Location to Group

Adds a location to the specified group.

```php
use Darkterminal\TursoPlatformAPI\core\Enums\Location;

$turso->groups()->addLocation('punk', Location::AMS)->get();
```

Ref: https://docs.turso.tech/api-reference/groups/add-location

## Remove Location from Group

Removes a location from the specified group.

```php
use Darkterminal\TursoPlatformAPI\core\Enums\Location;

$turso->groups()->deleteLocation('punk', Location::AMS)->get();
```

Ref: https://docs.turso.tech/api-reference/groups/remove-location

## Transfer Group

Transfer a group to another organization that you own or a member of.

```php
$turso->groups()->transfer('punk', 'universe')->get();
```

Ref: https://docs.turso.tech/api-reference/groups/transfer

## Unarchive Group

Unarchive a group that has been archived due to inactivity.

```php
$turso->groups()->unarchive('punk')->get();
```

Ref: https://docs.turso.tech/api-reference/groups/unarchive

## Update Databases in a Group

Updates all databases in the group to the latest libSQL version.

```php
$turso->groups()->updateVersion('punk')->get();
```

Ref: https://docs.turso.tech/api-reference/groups/update-database-versions

## Create Group Auth Token

Generates an authorization token for the specified group.

```php
use Darkterminal\TursoPlatformAPI\core\Enums\Authorization;

// Create group token that never expired with full-access permission
$turso->groups()->createToken('punk')->get();
// Create group token that will be expired in 2 week 1 day 30 minutes with full-access permission
$turso->groups()->createToken('punk', '2w1d30m')->get();
// Create group token that will be expired in 2 week 1 day 30 minutes with read-only permission
$turso->groups()->createToken('punk', '2w1d30m', Authorization::READ_ONLY)->get();
```

Ref: https://docs.turso.tech/api-reference/groups/create-token

## Invalidate All Group Auth Tokens

Invalidates all authorization tokens for the specified group.

```php
$turso->groups()->invalidateTokens('punk')->get();
```

Ref: https://docs.turso.tech/api-reference/groups/invalidate-tokens

> Turso Groups: https://docs.turso.tech/api-reference/groups
