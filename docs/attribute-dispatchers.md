---
sidebar_position: 2
---

# Attribute Dispatchers

Attribute dispatchers provide a declarative way to define feature flags directly in your class code using PHP 8
attributes. This approach makes it easy to see which methods are controlled by feature flags without cluttering your
dispatcher configuration.

## Defining the Attribute

Use the `#[FeatureFlagAttribute]` attribute to mark methods that should be executed based on feature flag conditions:

```php
use ByJG\FeatureFlag\Attributes\FeatureFlagAttribute;

class NotificationService
{
    #[FeatureFlagAttribute('email-notifications', 'enabled')]
    public function sendEmail(string $to, string $subject): void
    {
        // This method executes only when 'email-notifications' flag is 'enabled'
        echo "Sending email to {$to}\n";
    }

    #[FeatureFlagAttribute('sms-notifications')]
    public function sendSMS(string $phone): void
    {
        // This method executes when 'sms-notifications' flag is set (any value)
        echo "Sending SMS to {$phone}\n";
    }

    #[FeatureFlagAttribute('push-notifications', 'beta')]
    public function sendPushNotification(string $deviceId): void
    {
        // This method executes only when 'push-notifications' flag is 'beta'
        echo "Sending push notification\n";
    }
}
```

## Attribute Syntax

The `FeatureFlagAttribute` accepts two parameters:

```php
#[FeatureFlagAttribute(string $featureFlag, ?string $featureValue = null)]
```

- **`$featureFlag`** (required): The name of the feature flag to check
- **`$featureValue`** (optional): The specific value the flag must have. If omitted, any value will match

## Adding to the Dispatcher

Use the `addClass()` method to automatically register all attributed methods:

```php
use ByJG\FeatureFlag\FeatureFlags;
use ByJG\FeatureFlag\FeatureFlagDispatcher;

// Define feature flags
FeatureFlags::addFlag('email-notifications', 'enabled');
FeatureFlags::addFlag('sms-notifications', 'on');

// Create dispatcher
$dispatcher = new FeatureFlagDispatcher();

// Add the class - all methods with FeatureFlagAttribute will be registered
$dispatcher->addClass(NotificationService::class);

// Dispatch with arguments
$dispatcher->dispatch('user@example.com', 'Hello', '+1234567890');
```

## How It Works

When you call `addClass()`, the dispatcher:

1. Uses reflection to scan the class for methods with `FeatureFlagAttribute`
2. Creates a `StaticMethodHandler` for each attributed method
3. Registers the handler with the appropriate selector

:::info
The dispatcher automatically handles both static and instance methods. For instance methods, a new instance of the class
is created automatically.
:::

## Static vs Instance Methods

Both static and instance methods are supported:

```php
class FeatureService
{
    #[FeatureFlagAttribute('feature-a')]
    public static function staticHandler(): void
    {
        // Called as FeatureService::staticHandler()
        echo "Static method executed\n";
    }

    #[FeatureFlagAttribute('feature-b')]
    public function instanceHandler(): void
    {
        // A new instance is created and this method is called
        echo "Instance method executed\n";
    }
}
```

## Passing Arguments

Attributed methods receive the same arguments passed to `dispatch()`:

```php
class LogService
{
    #[FeatureFlagAttribute('detailed-logging')]
    public function logDetails(string $message, array $context): void
    {
        echo "Logging: {$message} with context: " . json_encode($context) . "\n";
    }
}

// Usage
$dispatcher->addClass(LogService::class);
$dispatcher->dispatch('User logged in', ['user_id' => 123, 'ip' => '192.168.1.1']);
```

## Multiple Flags in One Class

You can have multiple methods with different feature flags in the same class:

```php
class PaymentService
{
    #[FeatureFlagAttribute('stripe-payment')]
    public function processStripe(float $amount): void
    {
        echo "Processing Stripe payment: \${$amount}\n";
    }

    #[FeatureFlagAttribute('paypal-payment')]
    public function processPayPal(float $amount): void
    {
        echo "Processing PayPal payment: \${$amount}\n";
    }

    #[FeatureFlagAttribute('crypto-payment', 'enabled')]
    public function processCrypto(float $amount): void
    {
        echo "Processing Crypto payment: \${$amount}\n";
    }
}
```

## Limitations

- Only public methods can be decorated with `FeatureFlagAttribute`
- Methods must be either static or have a parameterless constructor for their class
- Private and protected methods are ignored

## Best Practices

1. **Group Related Handlers**: Keep feature flag handlers in dedicated service classes
2. **Clear Naming**: Use descriptive method names that indicate what feature they control
3. **Documentation**: Add docblocks to explain what each feature flag controls
4. **Testing**: Test both when flags are enabled and disabled

## See Also

- [Handler Interface](handler-interface.md) - Create custom handlers
- [FeatureFlagSelector](featureflag-selector.md) - Manual selector configuration
- [Passing Arguments](passing-arguments.md) - How arguments are passed to handlers
