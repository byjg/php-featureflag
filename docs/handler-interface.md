---
sidebar_position: 1
---

# Handler Interface

All feature flag handlers must implement the `FeatureFlagHandlerInterface`, which provides a consistent way to execute
code when feature flags are matched.

## Interface Definition

```php
namespace ByJG\FeatureFlag;

interface FeatureFlagHandlerInterface
{
    /**
     * Execute the feature flag handler
     *
     * @param mixed ...$args Arguments to pass to the handler
     * @return mixed The result of the handler execution
     */
    public function execute(mixed ...$args): mixed;
}
```

## Creating a Handler

To create a custom handler, implement the `FeatureFlagHandlerInterface`:

```php
use ByJG\FeatureFlag\FeatureFlagHandlerInterface;

class MyFeatureHandler implements FeatureFlagHandlerInterface
{
    public function __construct(
        private string $message
    ) {
    }

    public function execute(mixed ...$args): mixed
    {
        echo $this->message . "\n";

        // You can use the passed arguments
        if (!empty($args)) {
            echo "Arguments: " . implode(', ', $args) . "\n";
        }

        return true;
    }
}
```

## Using the Handler

```php
use ByJG\FeatureFlag\FeatureFlags;
use ByJG\FeatureFlag\FeatureFlagDispatcher;
use ByJG\FeatureFlag\FeatureFlagSelector;

// Define feature flags
FeatureFlags::addFlag('new-feature', 'enabled');

// Create dispatcher
$dispatcher = new FeatureFlagDispatcher();

// Add handler
$handler = new MyFeatureHandler('Feature is enabled!');
$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('new-feature', 'enabled', $handler)
);

// Dispatch (with optional arguments)
$dispatcher->dispatch('arg1', 'arg2');
```

## Handler with Dependencies

You can inject dependencies into your handlers through the constructor:

```php
class EmailNotificationHandler implements FeatureFlagHandlerInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {
    }

    public function execute(mixed ...$args): mixed
    {
        $this->logger->info('Email notification feature triggered');

        $email = $args[0] ?? null;
        if ($email) {
            $this->mailer->send($email);
        }

        return null;
    }
}

// Usage with DI container
$handler = new EmailNotificationHandler($mailer, $logger);
$dispatcher->add(
    FeatureFlagSelector::whenFlagIsSet('email-notifications', $handler)
);
```

## Stateful Handlers

Handlers can maintain state between executions:

```php
class CounterHandler implements FeatureFlagHandlerInterface
{
    private int $count = 0;

    public function execute(mixed ...$args): mixed
    {
        $this->count++;
        echo "Handler executed {$this->count} times\n";
        return $this->count;
    }
}
```

:::warning
Be careful with stateful handlers when the same handler instance is used across multiple dispatchers or in concurrent
environments.
:::

## Best Practices

1. **Single Responsibility**: Each handler should handle one specific feature or behavior
2. **Immutability**: Prefer immutable handlers when possible
3. **Type Safety**: Use proper type hints for constructor parameters
4. **Error Handling**: Handle exceptions within the `execute()` method when appropriate
5. **Return Values**: Return meaningful values that can be used for testing or chaining

## See Also

- [FeatureFlagSelector](featureflag-selector.md) - Single condition selectors
- [FeatureFlagSelectorSet](featureflag-selectorset.md) - Multiple condition selectors
- [Passing Arguments](passing-arguments.md) - How to pass arguments to handlers
