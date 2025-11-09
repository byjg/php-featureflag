---
sidebar_position: 5
---

# Search Order

The dispatcher can search for and execute handlers in different orders depending on your use case. The search order
determines how the dispatcher iterates through selectors and feature flags to find matches.

## Available Search Orders

The `SearchOrder` enum provides three strategies:

- **`SearchOrder::Selector`** (Default) - Iterate by the order selectors were added
- **`SearchOrder::FeatureFlags`** - Iterate by the order feature flags were defined
- **`SearchOrder::Custom`** - Iterate by a custom list of specific flags

## Choosing the Right Order

| Order                       | When to Use                        | Performance Benefit                 |
|-----------------------------|------------------------------------|-------------------------------------|
| `SearchOrder::Selector`     | Fewer selectors than feature flags | Reduces iterations over selectors   |
| `SearchOrder::FeatureFlags` | Fewer feature flags than selectors | Reduces iterations over flags       |
| `SearchOrder::Custom`       | Want to check only specific flags  | Limits scope to relevant flags only |

## SearchOrder::Selector (Default)

Iterates through selectors in the order they were added to the dispatcher:

```php
use ByJG\FeatureFlag\SearchOrder;
use ByJG\FeatureFlag\FeatureFlagDispatcher;

$dispatcher = new FeatureFlagDispatcher();

// Add selectors
$dispatcher->add($selector1);
$dispatcher->add($selector2);
$dispatcher->add($selector3);

// Use default Selector order (explicit - not required)
$dispatcher->withSearchOrder(SearchOrder::Selector);

// Dispatches in order: selector1 → selector2 → selector3
$dispatcher->dispatch();
```

**Best for:**

- Small number of selectors
- Priority-based execution
- Most common use case

## SearchOrder::FeatureFlags

Iterates through feature flags in the order they were defined:

```php
use ByJG\FeatureFlag\SearchOrder;
use ByJG\FeatureFlag\FeatureFlags;
use ByJG\FeatureFlag\FeatureFlagDispatcher;

// Define flags
FeatureFlags::addFlag('flag-a', 'value1');
FeatureFlags::addFlag('flag-b', 'value2');
FeatureFlags::addFlag('flag-c', 'value3');

$dispatcher = new FeatureFlagDispatcher();

// Add many selectors
$dispatcher->add($selectorForFlagA);
$dispatcher->add($selectorForFlagB);
$dispatcher->add($anotherSelectorForFlagA);

// Use FeatureFlags order
$dispatcher->withSearchOrder(SearchOrder::FeatureFlags);

// Dispatches in flag order: flag-a → flag-b → flag-c
$dispatcher->dispatch();
```

**Best for:**

- Many selectors for few flags
- Flag-priority scenarios
- When flag order matters more than selector order

## SearchOrder::Custom

Limits execution to a specific subset of feature flags:

```php
use ByJG\FeatureFlag\SearchOrder;
use ByJG\FeatureFlag\FeatureFlags;
use ByJG\FeatureFlag\FeatureFlagDispatcher;

// Define many flags
FeatureFlags::addFlag('feature-1', 'on');
FeatureFlags::addFlag('feature-2', 'on');
FeatureFlags::addFlag('feature-3', 'on');
FeatureFlags::addFlag('feature-4', 'on');
FeatureFlags::addFlag('feature-5', 'on');

$dispatcher = new FeatureFlagDispatcher();

// Add selectors for all features
$dispatcher->add($selector1);
$dispatcher->add($selector2);
$dispatcher->add($selector3);
$dispatcher->add($selector4);
$dispatcher->add($selector5);

// Only check feature-2 and feature-4 (in that order)
$dispatcher->withSearchOrder(SearchOrder::Custom, ['feature-2', 'feature-4']);

// Only selectors matching feature-2 and feature-4 execute
$dispatcher->dispatch();
```

**Best for:**

- Conditional feature execution
- Testing specific flags
- Performance optimization when many flags exist but only a few matter
- Runtime flag filtering

## Setting the Search Order

Use the `withSearchOrder()` method before dispatching:

```php
$dispatcher->withSearchOrder(SearchOrder::FeatureFlags);
$dispatcher->dispatch();
```

:::warning
When using `SearchOrder::Custom`, you **must** provide the array of flag names as the second parameter. An exception is
thrown if the array is empty or if you provide flags without using Custom mode.
:::

## Examples

### Priority-Based Execution

```php
// High priority handlers first
$dispatcher->add($criticalHandler);
$dispatcher->add($importantHandler);
$dispatcher->add($normalHandler);

$dispatcher->withSearchOrder(SearchOrder::Selector); // Default
$dispatcher->dispatch();
```

### Feature-First Execution

```php
// Define flags in priority order
FeatureFlags::addFlag('security', 'enabled');
FeatureFlags::addFlag('performance', 'enabled');
FeatureFlags::addFlag('ui', 'enabled');

$dispatcher->withSearchOrder(SearchOrder::FeatureFlags);
$dispatcher->dispatch();
// Executes handlers in flag definition order
```

### Selective Feature Testing

```php
// Only test payment-related features
$dispatcher->withSearchOrder(SearchOrder::Custom, [
    'stripe-payment',
    'paypal-payment',
    'invoice-generation'
]);

$dispatcher->dispatch();
// Only handlers for these three flags execute
```

## Performance Considerations

### Selector Order (Default)

- **Iterations**: Number of selectors
- **Best when**: selectors < flags
- **Worst case**: O(selectors × flags)

### FeatureFlags Order

- **Iterations**: Number of flags
- **Best when**: flags < selectors
- **Worst case**: O(flags × selectors)

### Custom Order

- **Iterations**: Number of custom flags
- **Best when**: You only need a subset
- **Worst case**: O(custom_flags × selectors)

## Error Handling

```php
// ❌ Wrong - Custom requires flags array
try {
    $dispatcher->withSearchOrder(SearchOrder::Custom);
} catch (\InvalidArgumentException $e) {
    echo "Must provide flags when using Custom search order";
}

// ❌ Wrong - Can't provide flags without Custom
try {
    $dispatcher->withSearchOrder(SearchOrder::Selector, ['flag1']);
} catch (\InvalidArgumentException $e) {
    echo "Must not provide flags when not using Custom search order";
}

// ✅ Correct
$dispatcher->withSearchOrder(SearchOrder::Custom, ['flag1', 'flag2']);
```

## See Also

- [FeatureFlagSelector](featureflag-selector.md) - Creating selectors
- [FeatureFlagSelectorSet](featureflag-selectorset.md) - Multi-condition selectors
- [Passing Arguments](passing-arguments.md) - Passing data to handlers
