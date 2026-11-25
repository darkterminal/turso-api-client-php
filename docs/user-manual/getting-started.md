---
title: Getting started
description: Install the SDK, configure a Platform API token, and make your first Turso Platform API call.
section: Introduction
eyebrow: Quick start
---

## Install

Install with Composer:

```bash
composer require darkterminal/turso-api-client-php
```

The package requires PHP 8.3+, `ext-curl`, and `ext-json`.

## Create a Platform API token

The SDK uses a **Turso Platform API token**, not a database token.

- Create/manage tokens in the Turso dashboard or via Turso CLI.
- Keep the token in an environment variable or secret manager.
- Do not hardcode tokens in source files or commit them.

```bash
export TURSO_PLATFORM_API_TOKEN="your-platform-token"
```

## Build a client

Use the immutable builder:

```php
<?php

use Darkterminal\TursoPlatformAPI\Client\Client;

require __DIR__ . '/vendor/autoload.php';

$client = Client::builder()
    ->withToken($_ENV['TURSO_PLATFORM_API_TOKEN'])
    ->withOrganization('acme') // optional default org slug
    ->build();
```

The builder validates configuration at `build()` time. Invalid tokens, malformed base URLs, or invalid timeouts throw `ConfigurationException`.

## First requests

### Validate token

```php
$validation = $client->auth()->validate();
// ['exp' => 1735689600] or contract-defined payload
```

### Get current user

```php
$user = $client->user()->get();
```

### List databases

```php
$databases = $client->databases()->list();
// ['databases' => [...]]
```

### Create a database

```php
$created = $client->databases()->create(
    name: 'app-db',
    group: 'default',
);
```

### Create a database token

```php
use Darkterminal\TursoPlatformAPI\Enums\Authorization;

$token = $client->databases()->createToken(
    databaseName: 'app-db',
    expiration: 'never',
    authorization: Authorization::FULL_ACCESS,
);

// ['jwt' => '...']
```

The returned JWT is a database token. Treat it as a secret.

## Error handling

Catch SDK exceptions at application boundaries:

```php
use Darkterminal\TursoPlatformAPI\Exceptions\AuthenticationException;
use Darkterminal\TursoPlatformAPI\Exceptions\NotFoundException;
use Darkterminal\TursoPlatformAPI\Exceptions\PlatformException;
use Darkterminal\TursoPlatformAPI\Exceptions\RateLimitException;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;

try {
    $db = $client->databases()->get('missing-db');
} catch (ValidationException $e) {
    // Input failed local validation before request.
} catch (AuthenticationException $e) {
    // 401/403
} catch (NotFoundException $e) {
    // 404
} catch (RateLimitException $e) {
    // 429; inspect $e->getRetryAfter() when available.
} catch (PlatformException $e) {
    // Any other SDK error.
}
```

## Next steps

- Read [Configuration](configuration.md) for custom transport, base URL, timeout, and organization IDs.
- Use [Databases](databases.md) and [Groups](groups.md) for core provisioning flows.
- Check [Errors and troubleshooting](errors-troubleshooting.md) for real contract quirks.