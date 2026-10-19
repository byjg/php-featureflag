<?php

namespace Tests;

use ByJG\FeatureFlag\Attributes\FeatureFlagAttribute;

class SampleAttributeService
{
    public static array $control = [];

    #[FeatureFlagAttribute('flag1', 'value1')]
    public function method1(): void
    {
        self::$control[] = 'method1';
    }

    #[FeatureFlagAttribute('flag3')]
    public function method2(): void
    {
        self::$control[] = 'method2';
    }

    #[FeatureFlagAttribute('flag2', 'value1')]
    public function method3($rg1, $arg2): void
    {
        self::$control[] = "method3:$rg1:$arg2";
    }

    #[FeatureFlagAttribute('flag2', 'value2')]
    public function method4($rg1, $arg2): void
    {
        self::$control[] = "method4:$rg1:$arg2";
    }

    #[FeatureFlagAttribute('flag4', '0')]
    public function method5(): void
    {
        self::$control[] = 'method5';
    }

    // Not public: must be ignored by addClass(), even though it is static
    #[FeatureFlagAttribute('flag1', 'value1')]
    private static function privateStatic(): void
    {
        self::$control[] = 'privateStatic';
    }

    #[FeatureFlagAttribute('flag1', 'value1')]
    protected static function protectedStatic(): void
    {
        self::$control[] = 'protectedStatic';
    }

    public static function clear(): void
    {
        self::$control = [];
    }

    public static function getControl(): array
    {
        return self::$control;
    }
}