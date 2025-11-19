<?php

namespace Tests;

use ByJG\FeatureFlag\FeatureFlagHandlerInterface;

class TestHandler implements FeatureFlagHandlerInterface
{
    private static ?string $control = null;

    public function __construct(
        private string $value
    )
    {
    }

    public function execute(mixed ...$args): mixed
    {
        if (count($args) === 0) {
            self::$control = $this->value;
        } else {
            self::$control = $this->value . ':' . implode(':', $args);
        }
        return null;
    }

    public static function getControl(): ?string
    {
        return self::$control;
    }

    public static function clearControl(): void
    {
        self::$control = null;
    }
}
