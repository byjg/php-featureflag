---
sidebar_position: 4
---

# FeatureFlagSelectorSet

The `FeatureFlagSelectorSet` class enables conditional execution based on **multiple** feature flag states. The handler
is executed only when **ALL** specified conditions are met (logical AND operation).

## Basic Usage

```php
use ByJG\FeatureFlag\FeatureFlags;
use ByJG\FeatureFlag\FeatureFlagDispatcher;
use ByJG\FeatureFlag\FeatureFlagSelectorSet;
use ByJG\FeatureFlag\FeatureFlagHandlerInterface;

// Define multiple flags
FeatureFlags::addFlag('premium-user', 'true');
FeatureFlags::addFlag('beta-access', 'enabled');
FeatureFlags::addFlag('region', 'US');

// Create handler
class PremiumBetaHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        echo "Premium beta feature unlocked!\n";
        return null;
    }
}

// Create a Dispatcher
$dispatcher = new FeatureFlagDispatcher();

// Create selector set - handler executes ONLY if ALL conditions match
$selectorSet = FeatureFlagSelectorSet::instance(new PremiumBetaHandler())
    ->whenFlagIs('premium-user', 'true')
    ->whenFlagIs('beta-access', 'enabled')
    ->whenFlagIsSet('region');

// Add to dispatcher
$dispatcher->add($selectorSet);

// Dispatch the request
$dispatcher->dispatch();
```

## How It Works

The `FeatureFlagSelectorSet` uses a **builder pattern** to define multiple conditions:

1. Start with `instance()` providing the handler
2. Chain `whenFlagIs()` and/or `whenFlagIsSet()` for each condition
3. Add to the dispatcher
4. The handler executes **only if ALL conditions match**

## Factory Method

### instance(FeatureFlagHandlerInterface $handler)

Creates a new selector set with the specified handler:

```php
$set = FeatureFlagSelectorSet::instance($handler);
```

## Condition Methods

### whenFlagIs(string $flag, string $value)

Adds a condition requiring the flag to have a specific value:

```php
$set->whenFlagIs('feature', 'enabled');
```

### whenFlagIsSet(string $flag)

Adds a condition requiring the flag to exist with any value:

```php
$set->whenFlagIsSet('debug-mode');
```

## Method Chaining

All condition methods return `$this`, enabling fluent chaining:

```php
$set = FeatureFlagSelectorSet::instance($handler)
    ->whenFlagIs('tier', 'premium')
    ->whenFlagIs('region', 'US')
    ->whenFlagIsSet('early-access');
```

## Examples

### A/B Testing with User Segments

```php
FeatureFlags::addFlag('user-segment', 'beta');
FeatureFlags::addFlag('experiment-ui', 'variant-b');

$dispatcher->add(
    FeatureFlagSelectorSet::instance($experimentalUIHandler)
        ->whenFlagIs('user-segment', 'beta')
        ->whenFlagIs('experiment-ui', 'variant-b')
);

// Handler only runs for beta users in variant-b
$dispatcher->dispatch();
```

### Regional Feature Rollout

```php
FeatureFlags::addFlag('feature-payments', 'enabled');
FeatureFlags::addFlag('region', 'EU');
FeatureFlags::addFlag('gdpr-compliant');

$dispatcher->add(
    FeatureFlagSelectorSet::instance($euPaymentHandler)
        ->whenFlagIs('feature-payments', 'enabled')
        ->whenFlagIs('region', 'EU')
        ->whenFlagIsSet('gdpr-compliant')
);

// Handler only runs when all three conditions are met
$dispatcher->dispatch();
```

### Feature with Multiple Prerequisites

```php
FeatureFlags::addFlag('database-migrated', 'v2');
FeatureFlags::addFlag('cache-enabled');
FeatureFlags::addFlag('feature-advanced-analytics', 'beta');

$dispatcher->add(
    FeatureFlagSelectorSet::instance($analyticsHandler)
        ->whenFlagIs('database-migrated', 'v2')
        ->whenFlagIsSet('cache-enabled')
        ->whenFlagIs('feature-advanced-analytics', 'beta')
);
```

## Multiple Selector Sets

You can add multiple selector sets with different condition combinations:

```php
// Enterprise users in US
$dispatcher->add(
    FeatureFlagSelectorSet::instance($enterpriseUSHandler)
        ->whenFlagIs('tier', 'enterprise')
        ->whenFlagIs('region', 'US')
);

// Enterprise users in EU
$dispatcher->add(
    FeatureFlagSelectorSet::instance($enterpriseEUHandler)
        ->whenFlagIs('tier', 'enterprise')
        ->whenFlagIs('region', 'EU')
);

// Premium users anywhere
$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('tier', 'premium', $premiumHandler)
);
```

## Comparison: Selector vs SelectorSet

| Feature        | FeatureFlagSelector                   | FeatureFlagSelectorSet                                 |
|----------------|---------------------------------------|--------------------------------------------------------|
| **Conditions** | Single flag                           | Multiple flags (ALL must match)                        |
| **Logic**      | Simple check                          | Logical AND                                            |
| **Creation**   | Direct factory methods                | Builder pattern                                        |
| **Use Case**   | Simple toggles                        | Complex multi-condition scenarios                      |
| **Syntax**     | `whenFlagIs($flag, $value, $handler)` | `instance($handler)->whenFlagIs(...)->whenFlagIs(...)` |

## When to Use SelectorSet

Use `FeatureFlagSelectorSet` when:

- ✅ A feature requires multiple conditions to be enabled
- ✅ You need logical AND behavior across flags
- ✅ Features depend on user attributes + feature state
- ✅ Implementing gradual rollouts with multiple criteria

Use `FeatureFlagSelector` when:

- ✅ A single flag controls the feature
- ✅ You need simple on/off toggles
- ✅ Independent feature flags

## Combining with Regular Selectors

You can mix both selector types in the same dispatcher:

```php
// Complex multi-condition
$dispatcher->add(
    FeatureFlagSelectorSet::instance($complexHandler)
        ->whenFlagIs('tier', 'enterprise')
        ->whenFlagIs('feature-x', 'beta')
);

// Simple single condition
$dispatcher->add(
    FeatureFlagSelector::whenFlagIsSet('logging', $loggingHandler)
);
```

## Internal Behavior

When dispatched, the `FeatureFlagSelectorSet`:

1. Checks each added condition in order
2. **All** conditions must evaluate to `true`
3. If all match, the handler executes
4. If any condition fails, the handler is skipped

## See Also

- [FeatureFlagSelector](featureflag-selector.md) - Single condition selectors
- [Handler Interface](handler-interface.md) - Creating custom handlers
- [Search Order](search-order.md) - Controlling execution order
- [Passing Arguments](passing-arguments.md) - Passing data to handlers
