<?php

namespace Tests;

use ByJG\FeatureFlag\FeatureFlagHandlerInterface;

class SampleService implements FeatureFlagHandlerInterface
{
    public function __construct(
        private string $methodName
    )
    {
    }

    private static ?string $control = null;

    public function execute(mixed ...$args): mixed
    {
        match ($this->methodName) {
            'method1' => $this->method1(),
            'method2' => $this->method2(),
            'method3' => $this->method3(...$args),
            'method4' => $this->method4(...$args),
            default => null,
        };
        return null;
    }

    private function method1(): void
    {
        self::$control = 'method1';
    }

    private function method2(): void
    {
        self::$control = 'method2';
    }

    private function method3($arg1, $arg2): void
    {
        self::$control = "method3:$arg1:$arg2";
    }

    private function method4($arg1, $arg2): void
    {
        self::$control = "method4:$arg1:$arg2";
    }

    public static function clear(): void
    {
        self::$control = null;
    }

    public static function getControl(): ?string
    {
        return self::$control;
    }
}