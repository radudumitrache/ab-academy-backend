# Custom Casts

This document describes the custom cast classes used in the AB Academy platform.

## TimeCast

`TimeCast` is a custom cast class that handles time values in the database. It's used to format time values as "HH:MM" (24-hour format) when retrieving them from the database.

### Usage

To use the `TimeCast` in a model, add it to the `$casts` array:

```php
protected $casts = [
    'schedule_time' => \App\Casts\TimeCast::class,
];
```

### Implementation

The `TimeCast` class implements the `CastsAttributes` interface from Laravel and provides two methods:

1. `get($model, string $key, $value, array $attributes)`: Formats the time value as "HH:MM" when retrieving from the database
2. `set($model, string $key, $value, array $attributes)`: Passes the value as-is when saving to the database

### Example

When a time value like "14:30:00" is retrieved from the database, `TimeCast` will format it as "14:30" for use in the application.

### File Location

The `TimeCast` class is located at `app/Casts/TimeCast.php`.
