# API Tokens

Create, validate and revoke API keys that can be used to access the API.

```php
<?php
use Darkterminal\TursoPlatformAPI\Client;

require_once __DIR__ . '/vendor/autoload.php';

$turso = new Client(getenv('ORG_NAME'), getenv('API_TOKEN'));
```

### List API Tokens

Returns a list of API tokens belonging to a user.

```php
$turso->apiTokens()->list()->get();
```

Ref: https://docs.turso.tech/api-reference/tokens/list

### Create API Token

Returns a new API token belonging to a user.

```php
$turso->apiTokens()->create('test-token')->get();
```

Ref: https://docs.turso.tech/api-reference/tokens/create

### Validate API Token

Validates an API token belonging to a user.

```php
$turso->apiTokens()->validate()->get();
```

Ref: https://docs.turso.tech/api-reference/tokens/validate

### Revoke API Token

Revokes the provided API token belonging to a user.

```php
$turso->apiTokens()->revoke('test-token')->get();
```

Ref: https://docs.turso.tech/api-reference/tokens/revoke

> Turso API Token: https://docs.turso.tech/api-reference/tokens
