# Passing Arguments

You can pass arguments to the dispatcher following the example below:

```php
<?php
$dispatcher = new FeatureFlagDispatcher();

// Add a Closure to the dispatcher
$dispatcher->add(FeatureFlagSelector::whenFlagIs('flag2', 'value1', function ($arg1, $arg2) {
    echo "arg1: $arg1, arg2: $arg2\n";
});

// Dispatch the request with the arguments
$dispatcher->dispatch('arg1', 'arg2');
```
