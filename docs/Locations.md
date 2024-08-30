# Lcoations

Lcoations Platform API - PHP Wrapper

## List Locations

Fetch all supported locations for primary and replica databases.

```php
$turso->locations()->getLocations()->get();
```

Ref: https://docs.turso.tech/api-reference/locations/list

## Closest Region

Returns the closest region to the user’s location.

```php
$turso->locations()->closestRegion()->get();
```

Ref: https://docs.turso.tech/api-reference/locations/closest-region

> Turso Locations: https://docs.turso.tech/api-reference/locations
