---
sidebar_position: 6
---

# Passing Arguments

The dispatcher supports passing runtime arguments to handlers. Arguments passed to `dispatch()` are forwarded to the
`execute()` method of matching handlers.

## Basic Usage

```php
use ByJG\FeatureFlag\FeatureFlags;
use ByJG\FeatureFlag\FeatureFlagDispatcher;
use ByJG\FeatureFlag\FeatureFlagSelector;
use ByJG\FeatureFlag\FeatureFlagHandlerInterface;

// Create handler that accepts arguments
class NotificationHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        [$userId, $message] = $args;
        echo "Notifying user {$userId}: {$message}\n";
        return null;
    }
}

// Setup
FeatureFlags::addFlag('notifications', 'enabled');
$dispatcher = new FeatureFlagDispatcher();
$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('notifications', 'enabled', new NotificationHandler())
);

// Dispatch with arguments
$dispatcher->dispatch(123, 'Your order has shipped!');
// Output: Notifying user 123: Your order has shipped!
```

## Variadic Arguments

Handlers receive all arguments via the variadic `...$args` parameter:

```php
class LogHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        // Access all arguments
        echo "Received " . count($args) . " arguments\n";

        foreach ($args as $index => $arg) {
            echo "Arg {$index}: " . var_export($arg, true) . "\n";
        }

        return null;
    }
}

// Call with any number of arguments
$dispatcher->dispatch('arg1', 'arg2', 'arg3');
$dispatcher->dispatch(42);
$dispatcher->dispatch();  // No arguments
```

## Type Safety

Use type hints and destructuring for type-safe argument handling:

```php
class PaymentHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        // Destructure with validation
        if (count($args) < 2) {
            throw new \InvalidArgumentException('Missing required arguments');
        }

        [$amount, $currency, $metadata] = array_pad($args, 3, []);

        // Type checking
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('Amount must be numeric');
        }

        echo "Processing payment: {$amount} {$currency}\n";
        return true;
    }
}
```

## Complex Arguments

Pass arrays, objects, or any PHP type:

```php
class UserActivityHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        [$user, $action, $metadata] = $args;

        // Work with objects
        echo "User {$user->getName()} performed: {$action}\n";

        // Work with arrays
        echo "Metadata: " . json_encode($metadata) . "\n";

        return null;
    }
}

// Pass complex data
$user = new User('john@example.com');
$dispatcher->dispatch($user, 'login', ['ip' => '192.168.1.1', 'device' => 'mobile']);
```

## Multiple Handlers, Same Arguments

All matching handlers receive the same arguments:

```php
class EmailHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        [$email, $subject, $body] = $args;
        echo "Sending email to: {$email}\n";
        return null;
    }
}

class SMSHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        [$phone, $message] = $args;
        echo "Sending SMS to: {$phone}\n";
        return null;
    }
}

// Setup
FeatureFlags::addFlag('email-enabled');
FeatureFlags::addFlag('sms-enabled');

$dispatcher->add(
    FeatureFlagSelector::whenFlagIsSet('email-enabled', new EmailHandler())
);
$dispatcher->add(
    FeatureFlagSelector::whenFlagIsSet('sms-enabled', new SMSHandler())
);

// Both handlers receive the same arguments
$dispatcher->dispatch('user@example.com', 'Hello', 'Message body');
```

## Arguments with Attributes

Attributed methods also receive arguments:

```php
use ByJG\FeatureFlag\Attributes\FeatureFlagAttribute;

class NotificationService
{
    #[FeatureFlagAttribute('push-notifications')]
    public function sendPush(string $deviceId, string $title, string $body): void
    {
        echo "Push to {$deviceId}: {$title}\n";
    }

    #[FeatureFlagAttribute('email-digest')]
    public function sendDigest(User $user, array $items): void
    {
        echo "Digest for {$user->getEmail()} with " . count($items) . " items\n";
    }
}

// Usage
$dispatcher->addClass(NotificationService::class);
$dispatcher->dispatch($device, 'New Message', 'You have a new message');
```

## Optional Arguments

Handle optional arguments gracefully:

```php
class ConfigurableHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        // Provide defaults for optional arguments
        $message = $args[0] ?? 'Default message';
        $priority = $args[1] ?? 'normal';
        $metadata = $args[2] ?? [];

        echo "Message: {$message} (Priority: {$priority})\n";

        return null;
    }
}

// Call with different argument counts
$dispatcher->dispatch('Hello');                          // Uses defaults
$dispatcher->dispatch('Important', 'high');              // Custom priority
$dispatcher->dispatch('Full', 'high', ['key' => 'val']); // All arguments
```

## Return Values

Handlers can return values, though dispatch() itself returns the count of executed handlers:

```php
class ValidationHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        [$data] = $args;

        // Validate and return result
        if (empty($data)) {
            return ['valid' => false, 'error' => 'Data is empty'];
        }

        return ['valid' => true];
    }
}

// The count is returned, not the handler's return value
$count = $dispatcher->dispatch($userData);
echo "Executed {$count} handlers\n";
```

:::tip
If you need to collect return values from handlers, consider passing a collector object as an argument that handlers can
write to.
:::

## Argument Patterns

### Command Pattern

```php
class Command
{
    public function __construct(
        public string $action,
        public array $params
    ) {}
}

class CommandHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        /** @var Command $command */
        $command = $args[0];

        echo "Executing: {$command->action}\n";
        var_dump($command->params);

        return null;
    }
}

$command = new Command('create_user', ['email' => 'user@example.com']);
$dispatcher->dispatch($command);
```

### Context Pattern

```php
class ExecutionContext
{
    public function __construct(
        public User $user,
        public Request $request,
        public array $results = []
    ) {}

    public function addResult(string $key, mixed $value): void
    {
        $this->results[$key] = $value;
    }
}

class FeatureAHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        /** @var ExecutionContext $context */
        $context = $args[0];
        $context->addResult('feature-a', 'executed');
        return null;
    }
}

// Multiple handlers can share and modify the context
$context = new ExecutionContext($user, $request);
$dispatcher->dispatch($context);

// Check results after dispatch
var_dump($context->results);
```

## Best Practices

1. **Document Expected Arguments**: Use docblocks to document what arguments handlers expect
2. **Validate Arguments**: Always validate argument types and presence
3. **Use Defaults**: Provide sensible defaults for optional arguments
4. **Consider Types**: Use value objects or DTOs for complex argument sets
5. **Avoid Side Effects**: Be mindful that all handlers receive the same arguments

## See Also

- [Handler Interface](handler-interface.md) - Creating custom handlers
- [FeatureFlagSelector](featureflag-selector.md) - Single condition selectors
- [Attribute Dispatchers](attribute-dispatchers.md) - Using attributes with arguments

