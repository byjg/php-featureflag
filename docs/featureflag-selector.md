# FeatureFlagSelector

The `FeatureFlagSelector` class defines a way to filter the feature flags and if the condition is met
when the dispatcher is called, it will execute a function.

## Basic Usage

```php
<?php
// Create a Dispatcher
$dispatcher = new FeatureFlagDispatcher();

// Add a feature flag handler
$dispatcher->add(FeatureFlagSelector::whenFlagIs('flag2', 'value1', function () {/** function1 */}));
$dispatcher->add(FeatureFlagSelector::whenFlagIs('flag2', 'value2', function () {/** function2 */}));
$dispatcher->add(FeatureFlagSelector::whenFlagIs('flag2', 'value3', [Someclass::class, 'method1']));

// Dispatch the request    
$dispatcher->dispatch();
```

## Reference

### whenFlagIs(string \$flag, string \$value, callable|array \$function)

This static method creates a new instance of the `FeatureFlagSelector` class with the condition that the flag is 
equal to the value. If the condition is met, the function will be executed.

### whenFlagIsSet(string \$flag, callable|array \$function)

This static method creates a new instance of the `FeatureFlagSelector` class with the condition that the flag is set
with any value. If the condition is met, the function will be executed.

### stopPropagation()

When the condition is met, the dispatcher will stop the propagation and will not execute the next condition.
The default behavior is to continue the propagation.

 