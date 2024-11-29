# Callable Dispatchers

You can define a callable dispatcher to handle the feature flag.

## Adding to the Dispatcher

```php
<?php
$dispatcher = new FeatureFlagDispatcher();

// Add a Closure to the dispatcher
$dispatcher->add(FeatureFlagSelector::whenFlagIs('flag2', 'value1', function () {/** function1 */}));

// Add a Callable as array to the dispatcher
$dispatcher->add(FeatureFlagSelector::whenFlagIs('flag2', 'value2', [Someclass::class, 'method1']));

// Dispatch the request
$dispatcher->dispatch();
```

## Types of FeatureFlagSelector

- `FeatureFlagSelector::whenFlagIs($flag, $value, $callable)`: Execute the callable when the flag is equal to the value
- `FeatureFlagSelector::whenFlagIsSet($flag, $callable)`: Execute the callable when the flag is set with any value
