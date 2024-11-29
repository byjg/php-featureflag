# Attribute Dispatchers

The attribute dispatchers are a way to define the feature flags in a more readable way directly in the class code.

## Defining the Attribute

The example below will define that the method `method1` will be executed when the feature flag `flag1` is `value1` 
and/or the method `method2` will be executed when the feature flag `flag3` is set with any value.

```php
<?php
class SampleAttributeService
{
    public static array $control = [];

    #[FeatureFlagAttribute('flag1', 'value1')]
    public function method1(): void
    {
        self::$control[] = 'method1';
    }

    #[FeatureFlagAttribute('flag3')]
    public function method2(): void
    {
        self::$control[] = 'method2';
    }
}
```

## Adding to the Dispatcher

```php
<?php
$dispatcher = new FeatureFlagDispatcher();

// Add the class to the dispatcher
$dispatcher->addClass(SampleAttributeService::class);

// Dispatch the request
$dispatcher->dispatch();
```
