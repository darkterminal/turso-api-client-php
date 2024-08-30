# Members

Manage your members in organization.

## List Members

Returns a list of members part of the organization.

```php
$turso->members()->list()->get();
```

Ref: https://docs.turso.tech/api-reference/organizations/members/list

## Add Member

Add an existing Turso user to an organization.

```php
use Darkterminal\TursoPlatformAPI\core\Enums\RoleType;

$turso->members()->addMember('notrab', RoleType::ADMIN)->get();
```

## Remove Member

Remove a user from the organization by username.

```php
$turso->members()->removeMember('notrab')->get();
```

Ref: https://docs.turso.tech/api-reference/organizations/members/remove

> Turso Members: https://docs.turso.tech/api-reference/members
