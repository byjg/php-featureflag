# Changelog 6.0

## Overview

Version 6.0 represents a major release with significant architectural improvements and modernization of the codebase. This release introduces breaking changes that require code updates when upgrading from 5.x.

## New Features

### Handler Interface System
- Introduced `FeatureFlagHandlerInterface` for type-safe handler implementations
- Added `StaticMethodHandler` class for internal method invocation via reflection
- Handlers now use a standardized `execute(mixed ...$args): mixed` method signature

### FeatureFlagSelectorSet
- New `FeatureFlagSelectorSet` class for managing multiple feature flag conditions
- Allows grouping multiple flag conditions that must ALL match
- Provides fluent interface with `whenFlagIsSet()` and `whenFlagIs()` methods
- Simplifies complex multi-condition feature flag scenarios

### PSR-11 Container Integration
- Added PSR-11 container support to `FeatureFlags` class
- New `addFromContainer()` method for loading flags from dependency injection containers
- Enhanced flexibility for enterprise applications

### PHP 8 Attributes Support
- Enhanced attribute-based dispatcher support with `FeatureFlagAttribute`
- Methods can be decorated with attributes to automatically register as handlers
- `addClass()` method scans classes for attribute-decorated methods

### Improved Documentation
- Comprehensive documentation for handler interfaces
- New dedicated guides for:
  - Handler Interface usage
  - FeatureFlagSelectorSet patterns
  - Attribute-based dispatchers
  - Container integration
  - Passing arguments to handlers
  - Search order control

## Breaking Changes

| Component | Before (5.x) | After (6.0) | Description |
|-----------|-------------|-------------|-------------|
| PHP Version | `>=8.1 <8.4` | `>=8.3 <8.6` | Minimum PHP version raised to 8.3, added support for 8.4 and 8.5 |
| PHPUnit | `^9.5` | `^10.5\|^11.5` | Upgraded to PHPUnit 10/11 with new configuration format |
| Psalm | `^5.9` | `^5.9\|^6.13` | Added support for Psalm 6.x |
| Handler Type | `\Closure\|array $callable` | `FeatureFlagHandlerInterface $handler` | Callables replaced with typed interface implementations |
| Selector Constructor | `whenFlagIsSet($name, $callable)` | `whenFlagIsSet($name, $handler)` | Third parameter now requires `FeatureFlagHandlerInterface` |
| Method Return | `stopPropagation(): void` | `stopPropagation(): static` | Now returns `$this` for fluent chaining |
| Getter Method | `getCallable()` | `getHandler()` | Method renamed to reflect interface change |
| Test Annotations | PHPUnit doc-comments | PHP 8 attributes | Tests now use `#[Test]`, `#[DataProvider]` attributes |
| Data Providers | Instance methods | Static methods | All PHPUnit data providers must be static |

## Upgrade Path from 5.x to 6.x

### 1. Update PHP and Dependencies

Update your `composer.json`:

```json
{
  "require": {
    "php": ">=8.3 <8.6",
    "byjg/featureflag": "^6.0"
  }
}
```

Run:
```bash
composer update byjg/featureflag
```

### 2. Replace Callables with Handler Interface

**Before (5.x):**
```php
$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('my-flag', 'value', function($arg) {
        // handler code
    })
);
```

**After (6.0):**
```php
class MyHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        // handler code
        return null;
    }
}

$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('my-flag', 'value', new MyHandler())
);
```

### 3. Convert Callable Arrays to Handlers

**Before (5.x):**
```php
$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('flag', 'value', [MyClass::class, 'method'])
);
```

**After (6.0):**
```php
class MyClassHandler implements FeatureFlagHandlerInterface
{
    public function execute(mixed ...$args): mixed
    {
        return MyClass::method(...$args);
    }
}

$dispatcher->add(
    FeatureFlagSelector::whenFlagIs('flag', 'value', new MyClassHandler())
);
```

### 4. Update PHPUnit Tests (if extending this library)

**Before (5.x):**
```php
/**
 * @dataProvider myDataProvider
 */
public function testSomething($data)
{
    // test code
}

public function myDataProvider()
{
    return [/* data */];
}
```

**After (6.0):**
```php
#[Test]
#[DataProvider('myDataProvider')]
public function testSomething($data)
{
    // test code
}

public static function myDataProvider(): array
{
    return [/* data */];
}
```

### 5. Update Configuration Files

- Update `phpunit.xml.dist` to PHPUnit 10/11 format (remove deprecated attributes)
- Update `psalm.xml` if using custom Psalm configuration
- Ensure GitHub Actions workflows use updated container configurations

### 6. Verify Return Type Usage

If you were using `stopPropagation()`, you can now chain it:

```php
// 6.0 supports fluent chaining
$selector->stopPropagation()->someOtherMethod();
```

### 7. Test Your Application

After making these changes:

1. Run your test suite to ensure all handlers work correctly
2. Verify feature flag dispatching works as expected
3. Check static analysis with Psalm/PHPStan
4. Test in a staging environment before production deployment

## Bug Fixes

- Fixed PHPUnit 10+ compatibility issues with data provider methods
- Improved exception handling for invalid class and method references
- Enhanced type safety throughout the codebase
- Removed psalm suppressions that are no longer needed with interface-based approach

## Internal Improvements

- Modernized codebase to leverage PHP 8.3+ features
- Improved static analysis coverage and type safety
- Updated GitHub Actions workflows for better CI/CD
- Enhanced test coverage with container integration tests
- Cleaner separation of concerns with dedicated handler interface
- Removed legacy callable validation code

## Migration Support

For complex applications, consider:

1. Creating a temporary adapter class that wraps closures in the `FeatureFlagHandlerInterface`
2. Migrating handlers incrementally rather than all at once
3. Using the new `FeatureFlagSelectorSet` to simplify complex flag logic during migration
4. Leveraging container integration if using dependency injection

## Additional Resources

- [Handler Interface Documentation](docs/handler-interface.md)
- [FeatureFlagSelectorSet Documentation](docs/featureflag-selectorset.md)
- [Attribute Dispatchers Guide](docs/attribute-dispatchers.md)
- [Migration Examples in Tests](tests/)
