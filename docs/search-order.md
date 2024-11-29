# Search Order

The `dispatch()` will search for the feature flags to be executed in the possible orders:

- `SearchOrder::Selector`: by the order they were added to the dispatcher
- `SearchOrder::FeatureFlags`: by the order of the feature flags defined in the `FeatureFlags` class
- `SearchOrder::Custom`: by a custom order defined by the specific flags defined by the developer.

The default order is by the order they were added to the dispatcher.

The table below can guide you to define the search order:

| Order                       | When to use                                                                                         |
|-----------------------------|-----------------------------------------------------------------------------------------------------|
| `SearchOrder::Selector`     | When you have less selectors in the dispatch than the number of feature flags defined               |
| `SearchOrder::FeatureFlags` | When you have less feature flags defined than the number of selectors in the dispatch               |
| `SearchOrder::Custom`       | When you have in a single dispatch several selector, however you want to filter only specific flags |

## SearchOrder::Selector

```php
<?php
$dispatcher = new FeatureFlagDispatcher();

$dispatcher->withSearchOrder(SearchOrder::Selector);    // Default
$dispatcher->dispatch();
```

## SearchOrder::FeatureFlags

```php
<?php
$dispatcher = new FeatureFlagDispatcher();

$dispatcher->withSearchOrder(SearchOrder::FeatureFlags);
$dispatcher->dispatch();
```

## SearchOrder::Custom

```php
<?php
$dispatcher = new FeatureFlagDispatcher();

$dispatcher->withSearchOrder(SearchOrder::Custom, ['flag2', 'flag1']);
$dispatcher->dispatch();
```


