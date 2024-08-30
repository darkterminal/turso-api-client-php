# Audit Logs

Monitor logs of what's happening in your organization.

```php
<?php
use Darkterminal\TursoPlatformAPI\Client;

require_once __DIR__ . '/vendor/autoload.php';

$turso = new Client(getenv('ORG_NAME'), getenv('API_TOKEN'));
```

## List Audit Logs

Return the audit logs for the given organization, ordered by the `created_at` field in descending order.

```php
$turso->auditLogs()->list()->get();
// List 20 logs per page, page 1
$turso->auditLogs()->list(20, 1)->get();
```

Ref: https://docs.turso.tech/api-reference/audit-logs/list

> Turso Audit Logs: https://docs.turso.tech/api-reference/audit-logs
