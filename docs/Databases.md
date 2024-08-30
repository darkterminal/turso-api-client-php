# Databases API

Create, manage and generate auth tokens for databases.

```php
<?php
use Darkterminal\TursoPlatformAPI\Client;

require_once __DIR__ . '/vendor/autoload.php';

$turso = new Client(getenv('ORG_NAME'), getenv('API_TOKEN'));
```

## Create Database

Creates a new database in a group for the organization or user.

```php
// Option: 1 Create database in default group
$turso->databases()->create($databaseName)->get();
// Option: 2 Create database in default group and mark as Schema Database (parent)
$turso->databases()->create($databaseName, true)->get();
// Option: 3 Create database in default group and mark as Child database that relate with Parent Schema
$turso->databases()->create($databaseName, true, $parentDatabase)->get();
// Option: 4 Create database in default group and mark as Child database that relate with Parent Schema
$turso->databases()->create($databaseName, true, $parentDatabase)->get();
// Option: 5 Create database in default group and mark as Child database that relate with Parent Schema with size limit
// The maximum size of the database in bytes. Values with units are also accepted, e.g. 1mb, 256mb, 1gb
$turso->databases()->create($databaseName, true, $parentDatabase, $sizeLimit)->get();
// Option: 6 Create database in default group and mark as Child database that relate with Parent Schema with size limit
// The maximum size of the database in bytes. Values with units are also accepted, e.g. 1mb, 256mb, 1gb
$turso->databases()->create($databaseName, true, $parentDatabase, $sizeLimit)->get();
// Option: 7 Create database from seed in default group and mark as Child database that relate with Parent Schema with size limit
// The maximum size of the database in bytes. Values with units are also accepted, e.g. 1mb, 256mb, 1gb
$turso->databases()->create($databaseName, true, $parentDatabase, $sizeLimit, $seedMetadata)->get();

# Another shortcut that not short

$turso->databases()->createInGroup($databaseName, $group)->get();
$turso->databases()->createInGroupWithLimit($databaseName, $group, $sizeLimit)->get();
$turso->databases()->createWithLimit($databaseName, $sizeLimit)->get();
$turso->databases()->createFromSeed($databaseName, $seedMetadata)->get();
$turso->databases()->createFromSeedInGroup($databaseName, $seedMetadata, $group)->get();

# Multi-DB Schema related things

$turso->databases()->createParentSchema($databaseName)->get();
$turso->databases()->createChildSchema($databaseName, $parentSchema)->get();
$turso->databases()->craeteChildSchemaWithLimit($databaseName, $parentSchema, $sizeLimit)->get();
$turso->databases()->createParentSchemaInGroup($databaseName, $group)->get();
$turso->databases()->createChildSchemaInGroup($databaseName, $parentSchema, $group)->get();
$turso->databases()->createChildSchemaInGroupWithLimit($databaseName, $parentSchema, $group, $sizeLimit)->get();
```

Ref: https://docs.turso.tech/api-reference/databases/create

## Retrieve Database

Returns a database belonging to the organization or user.

```php
$turso->databases()->getDatabase($databaseName)->get();
```

Additional methods:

```php
$turso->databases()->getChildOfDatabases($databaseName)->get();
```

Ref: https://docs.turso.tech/api-reference/databases/retrieve

## Retrieve Database Configuration

Retrieve an individual database configuration belonging to the organization or user.

```php
$turso->databases()->getDatabaseConfiguration($databaseName)->get();
```

Ref: https://docs.turso.tech/api-reference/databases/configuration

## Update Database Configuration

Update a database configuration belonging to the organization or user.

```php
$configuration = [
    'allow_attach' => false, # bool
    'block_reads' => false, # bool
    'block_writes' => false, # bool
    # string: The maximum size of the database in bytes. Values with units are also accepted, e.g. 1mb, 256mb, 1gb
    'size_limit' => '',
];

$turso->databases()->updateDatabaseConfiguration($databaseName, $configuration)->get();
```

Ref: https://docs.turso.tech/api-reference/databases/update-configuration

## Retrieve Database Usage

Fetch activity usage for a database in a given time period.

```php
$turso->databases()->usage($databaseName)->get();
```

Ref: https://docs.turso.tech/api-reference/databases/usage

## Retrieve Database Stats

Fetch the top queries of a database, including the count of rows read and written.

```php
$turso->databases()->stats($databaseName)->get();
```

Ref: https://docs.turso.tech/api-reference/databases/stats

## Delete Database

Delete a database belonging to the organization or user.

```php
$turso->databases()->delete($databaseName)->get();
```

Ref: https://docs.turso.tech/api-reference/databases/delete

## List Database Instances

Returns a list of instances of a database. Instances are the individual primary or replica databases in each region defined by the group.

```php
$turso->databases()->listInstances($databaseName)->get();
```

Ref: https://docs.turso.tech/api-reference/databases/list-instances

## Retrieve Database Instance

Return the individual database instance by name.

```php
use Darkterminal\TursoPlatformAPI\core\Enums\Location;

$turso->databases()->getInstance($databaseName, Location::SIN)->get();
```

Ref: https://docs.turso.tech/api-reference/databases/retrieve-instance

## Generate Database Auth Token

Generates an authorization token for the specified database.

```php
use Darkterminal\TursoPlatformAPI\core\Enums\Authorization;

// Option: 1 Create database auth token that never expired and have full-access
$turso->databases()->createToken($databaseName)->get();
// Option: 2 Create database auth token that will expired in 2w1d30m (2 week 1 day 30 minute) and have full-access
$turso->databases()->createToken($databaseName, '2w1d30m')->get();
// Option: 3 Create database auth token that will expired in 2w1d30m (2 week 1 day 30 minute) and have read-only
$turso->databases()->createToken($databaseName, '2w1d30m', Authorization::READ_ONLY)->get();
// Option: 4 Create database auth token that will expired in 2w1d30m (2 week 1 day 30 minute) and have full-access and permission to Read ATTACH lists of databases
$turso->databases()->createToken($databaseName, '2w1d30m', Authorization::FULL_ACCESS, ['db1', 'db2'])->get();
```

Ref: https://docs.turso.tech/api-reference/databases/create-token

## Invalidate All Database Auth Tokens

Invalidates all authorization tokens for the specified database.

```php
$turso->databases()->invalidateTokens($databaseName)->get();
```

Ref: https://docs.turso.tech/api-reference/databases/invalidate-tokens

## Upload SQLite Dump

Upload a SQL dump to be used when [creating a new database](https://docs.turso.tech/api-reference/databases/create) from seed.

```php
$dumpFilePath = '/path/to/your/database/dump.sql';
$turso->databases()->uploadDump($dumpFilePath)->get();
```

Ref: https://docs.turso.tech/api-reference/databases/upload-dump

> Turso Databases: https://docs.turso.tech/api-reference/databases
