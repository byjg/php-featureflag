---
sidebar_key: featureflag
sidebar_category: infrastructure
tags: [php]
---

# FeatureFlag Dispatcher

Run code by feature flag without `if` blocks: register handlers, or mark methods with an attribute, and dispatch.

[![Sponsor](https://img.shields.io/badge/Sponsor-%23ea4aaa?logo=githubsponsors&logoColor=white&labelColor=0d1117)](https://github.com/sponsors/byjg)
[![Build Status](https://github.com/byjg/php-featureflag/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-featureflag/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](https://opensource.byjg.com)
[![Install MCP Server](https://img.shields.io/badge/Install-MCP_Server-8A2BE2?logo=modelcontextprotocol&logoColor=white)](https://opensource.byjg.com/docs/ai/mcpserver-byjg-docs/)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-featureflag/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-featureflag.svg)](https://opensource.byjg.com/license/)
[![GitHub release](https://img.shields.io/github/release/byjg/php-featureflag.svg)](https://github.com/byjg/php-featureflag/releases/)

Feature flags are a powerful technique for managing features in your application, enabling you to toggle functionality
on/off without deploying new code. This library provides a clean, interface-based approach to dispatch handlers based on
feature flag states.

## Why

Feature flags usually end up as `if ($flags->isEnabled('x'))` scattered through the code.
Each new flag adds branches, and removing a flag means finding every one of them.

FeatureFlag moves the decision to one place, the dispatcher. Each handler, or each method
marked with an attribute, declares the flag and value it runs for:

```php
class NotificationService
{
    #[FeatureFlagAttribute('email-notifications', 'enabled')]
    public function sendEmail(string $to, string $subject): void { /* ... */ }

    #[FeatureFlagAttribute('sms-notifications')]
    public function sendSms(string $to, string $subject): void { /* ... */ }
}

$dispatcher->addClass(NotificationService::class);
$dispatcher->dispatch($to, $subject);   // runs only the methods whose flag matches
```

Turning a feature on or off is a change to the flags, not to the code that uses it.

## Basic Usage

```php
use ByJG\FeatureFlag\FeatureFlags;
use ByJG\FeatureFlag\FeatureFlagDispatcher;
use ByJG\FeatureFlag\FeatureFlagSelector;
use ByJG\FeatureFlag\FeatureFlagHandlerInterface;

// Initialize the enabled features
FeatureFlags::addFlag('flag1', 'value1');
FeatureFlags::addFlag('flag2', 'value2');
FeatureFlags::addFlag('flag3');

// Create handler implementations
class MyHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        echo "Handler executed!\n";
        return null;
    }
}

// Create a Dispatcher
$dispatcher = new FeatureFlagDispatcher();

// Add feature flag handlers
$dispatcher->add(FeatureFlagSelector::whenFlagIs('flag2', 'value1', new MyHandler()));
$dispatcher->add(FeatureFlagSelector::whenFlagIs('flag2', 'value2', new MyHandler()));

// Dispatch the request
$dispatcher->dispatch();

// Since there is a feature flag 'flag2' with value 'value2', the corresponding handler will be executed
```

:::note
If one or more feature flags match the condition, **all matching handlers will be executed** in the order they were
added.
:::

## Managing the Flags

`FeatureFlags` keeps the enabled flags in a static list shared by every dispatcher:

```php
use ByJG\FeatureFlag\FeatureFlags;

FeatureFlags::addFlag('flag1', 'value1');              // a flag with a value
FeatureFlags::addFlag('flag3');                        // a flag with no value (null): only whenFlagIsSet() matches it
FeatureFlags::addFromContainer('flag4', $container);   // added only if the PSR-11 container has 'flag4'

FeatureFlags::hasFlag('flag1');   // true
FeatureFlags::getFlag('flag1');   // 'value1'; throws InvalidArgumentException if the flag was not added
FeatureFlags::getFlags();         // ['flag1' => 'value1', 'flag3' => null, ...]
FeatureFlags::clearFlags();       // remove every flag
```

## Handler Types

- [Handler Interface](docs/handler-interface.md) - Create custom handlers implementing `FeatureFlagHandlerInterface`
- [Attributes](docs/attribute-dispatchers.md) - Use PHP 8 attributes to mark methods as handlers

## Selector Types

- [FeatureFlagSelector](docs/featureflag-selector.md) - Single condition selectors
- [FeatureFlagSelectorSet](docs/featureflag-selectorset.md) - Multiple condition selectors (ALL must match)

## Advanced Usage

- [Search Order](docs/search-order.md) - Control the order of handler execution
- [Passing Arguments](docs/passing-arguments.md) - Pass runtime arguments to handlers

## Installation

```bash
composer require "byjg/featureflag"
```

## Requirements

- PHP 8.3 to 8.6
- PSR-11 Container (optional, for container integration)

## Running Tests

```bash
composer test
```

----
[Open source ByJG](https://opensource.byjg.com)
