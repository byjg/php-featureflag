<?php

namespace Tests;

class SampleService
{
    public static ?string $control = null;

    public function method1(): void
    {
        self::$control = 'method1';
    }

    public function method2(): void
    {
        self::$control = 'method2';
    }

    public function method3($rg1, $arg2): void
    {
        self::$control = "method3:$rg1:$arg2";
    }

    public function method4($rg1, $arg2): void
    {
        self::$control = "method4:$rg1:$arg2";
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