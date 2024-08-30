# Invites

Manage invites in your organization.

## List Invites

Returns a list of invites for the organization.

```php
$turso->invites()->list()->get();
```

Ref: https://docs.turso.tech/api-reference/organizations/invites/list

## Create Invite

Invite a user (who isn’t already a Turso user) to an organization.

```php
use Darkterminal\TursoPlatformAPI\core\Enums\RoleType;

$turso->invites()->createInvite('darkterminal@quack.com', RoleType::ADMIN)->get();
```

## Delete Invite

Delete an invite for the organization by email.

```php
$turso->invites()->deleteInvite('darkterminal@quack.com')->get();
```

Ref: https://docs.turso.tech/api-reference/organizations/invites/delete

> Turso Invites: https://docs.turso.tech/api-reference/invites
