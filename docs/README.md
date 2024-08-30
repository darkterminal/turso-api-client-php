## Usage

Create Turso Platform API instance

```php
<?php
use Darkterminal\TursoPlatformAPI\Client;

require_once __DIR__ . '/vendor/autoload.php';

$turso = new Client(getenv('ORG_NAME'), getenv('API_TOKEN'));
```

## Platforms APIs Usages

- [Databases](docs/Databases.md)
- [Groups](docs/Groups.md)
- [Locations](docs/Locations.md)
- [Organizations](docs/Organizations.md)
- [Members](docs/Members.md)
- [Invites](docs/Invites.md)
- [Audit Logs](docs/AuditLogs.md)
- [API Tokens](docs/APITokens.md)
