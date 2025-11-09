# FeatureFlagSelectorSet

The `FeatureFlagSelectorSet` class defines a way to filter the feature flags and if **all** the conditions met
when the dispatcher is called, it will execute a function.

## Basic Usage

```php
<?php
// Create a Dispatcher
$dispatcher = new FeatureFlagDispatcher();

// Set the feature flags
$set = FeatureFlagSelectorSet::instance(/* callable */)
            ->whenFlagIs(/* flag1 */, /* value1 */)
            ->whenFlagIsSet(/* flag2 */)


// Add a feature flag handler
$dispatcher->add($set);

// Dispatch the request    
$dispatcher->dispatch();
```

## Reference

The `FeatureFlagSelectorSet` class has the same methods as the `FeatureFlagSelector`.

The main difference is that the `FeatureFlagSelectorSet` will execute the function only if **all** the conditions are met.
 