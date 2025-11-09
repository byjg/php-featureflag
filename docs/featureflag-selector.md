---
sidebar_position: 3
---

# FeatureFlagSelector

The `FeatureFlagSelector` class defines conditional execution based on a single feature flag state. When the condition
is met during dispatch, the associated handler is executed.

## Basic Usage

```php
use ByJG\FeatureFlag\FeatureFlags;
use ByJG\FeatureFlag\FeatureFlagDispatcher;
use ByJG\FeatureFlag\FeatureFlagSelector;
use ByJG\FeatureFlag\FeatureFlagHandlerInterface;

// Define flags
FeatureFlags::addFlag('payment-method', 'stripe');

// Create handler
class StripePaymentHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        echo "Processing Stripe payment\n";
        return null;
    }
}

// Create a Dispatcher
$dispatcher = new FeatureFlagDispatcher();

// Add feature flag selectors with handlers
$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('payment-method', 'stripe', new StripePaymentHandler())
);
$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('payment-method', 'paypal', new PayPalPaymentHandler())
);

// Dispatch the request
$dispatcher->dispatch();
```

## Factory Methods

### whenFlagIs(string $flag, string $value, FeatureFlagHandlerInterface $handler)

Creates a selector that executes the handler **only when** the specified flag has the exact value:

```php
// Handler executes only when 'theme' flag is exactly 'dark'
$selector = FeatureFlagSelector::whenFlagIs('theme', 'dark', $darkThemeHandler);
```

**Parameters:**

- `$flag` - The feature flag name to check
- `$value` - The exact value the flag must have
- `$handler` - The handler to execute when the condition matches

### whenFlagIsSet(string $flag, FeatureFlagHandlerInterface $handler)

Creates a selector that executes the handler when the specified flag is set to **any value**:

```php
// Handler executes when 'debug-mode' flag exists with any value
$selector = FeatureFlagSelector::whenFlagIsSet('debug-mode', $debugHandler);
```

**Parameters:**

- `$flag` - The feature flag name to check
- `$handler` - The handler to execute when the flag is set

## Controlling Propagation

### stopPropagation()

By default, when multiple selectors match, **all** handlers are executed in order. Use `stopPropagation()` to prevent
subsequent handlers from executing after the current one:

```php
$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('feature', 'v1', $handlerV1)
        ->stopPropagation()
);

$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('feature', 'v1', $handlerV2)
);

// Only $handlerV1 executes because stopPropagation() was called
$dispatcher->dispatch();
```

:::warning
`stopPropagation()` only stops handlers added after the current selector. Previously added handlers that match will
still execute.
:::

## Examples

### Different Values for Same Flag

```php
FeatureFlags::addFlag('api-version', 'v2');

$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('api-version', 'v1', $apiV1Handler)
);
$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('api-version', 'v2', $apiV2Handler)
);
$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('api-version', 'v3', $apiV3Handler)
);

// Only $apiV2Handler executes
$dispatcher->dispatch();
```

### Fallback Handler

```php
FeatureFlags::addFlag('experimental-feature');

$dispatcher->add(
    FeatureFlagSelector::whenFlagIsSet('experimental-feature', $experimentalHandler)
);
$dispatcher->add(
    FeatureFlagSelector::whenFlagIsSet('stable-feature', $stableHandler)
);

// Only $experimentalHandler executes
$dispatcher->dispatch();
```

### Priority with stopPropagation

```php
// High priority handler
$dispatcher->add(
    FeatureFlagSelector::whenFlagIsSet('maintenance-mode', $maintenanceHandler)
        ->stopPropagation()
);

// Normal handlers (won't execute if maintenance-mode is set)
$dispatcher->add(
    FeatureFlagSelector::whenFlagIsSet('feature-a', $handlerA)
);
$dispatcher->add(
    FeatureFlagSelector::whenFlagIsSet('feature-b', $handlerB)
);
```

## Method Chaining

Selectors return `$this` from `stopPropagation()`, allowing method chaining during construction:

```php
$selector = FeatureFlagSelector::whenFlagIs('flag', 'value', $handler)
    ->stopPropagation();

$dispatcher->add($selector);
```

## Comparison with FeatureFlagSelectorSet

| Feature    | FeatureFlagSelector    | FeatureFlagSelectorSet                    |
|------------|------------------------|-------------------------------------------|
| Conditions | Single flag condition  | Multiple flag conditions (ALL must match) |
| Use Case   | Simple on/off features | Complex multi-flag scenarios              |
| Syntax     | Direct creation        | Builder pattern                           |

## See Also

- [FeatureFlagSelectorSet](featureflag-selectorset.md) - Multiple condition selectors
- [Handler Interface](handler-interface.md) - Creating custom handlers
- [Search Order](search-order.md) - Controlling execution order
- [Passing Arguments](passing-arguments.md) - Passing data to handlers
