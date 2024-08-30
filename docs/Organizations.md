# Organizations

Manage your organization platform.

### List Organizations

Returns a list of organizations the authenticated user owns or is a member of.

```php
$turso->organizations()->list()->get();
```

Ref: https://docs.turso.tech/api-reference/organizations/list

### Update Organization

Returns a list of organizations the authenticated user owns or is a member of.

```php
$turso->organizations()->update(getenv('ORG_NAME'))->get();
```

Ref: https://docs.turso.tech/api-reference/organizations/update

### List Plans

Returns a list of available plans and their quotas.

```php
$turso->organizations()->plans(getenv('ORG_NAME'))->get();
```

Ref: https://docs.turso.tech/api-reference/organizations/plans

### Current Subscription

Returns the current subscription details for the organization.

```php
$turso->organizations()->subscription(getenv('ORG_NAME'))->get();
```

Ref: https://docs.turso.tech/api-reference/organizations/subscription

### List Invoices

Returns a list of invoices for the organization.

```php
$turso->organizations()->invoices(getenv('ORG_NAME'))->get();
```

Ref: https://docs.turso.tech/api-reference/organizations/invoices

### Organization Usage

Fetch current billing cycle usage for an organization.

```php
$turso->organizations()->currentUsage(getenv('ORG_NAME'))->get();
```

Ref: https://docs.turso.tech/api-reference/organizations/usage

> Turso Organizations: https://docs.turso.tech/api-reference/organizations
