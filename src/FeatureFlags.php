<?php

namespace ByJG\FeatureFlag;

class FeatureFlags
{
    protected static array $flags = [];

    public static function addFlag(string $flagName, ?string $flagValue = null): void
    {
        self::$flags[$flagName] = $flagValue;
    }

    public static function hasFlag(string $flagName): bool
    {
        return array_key_exists($flagName, self::$flags);
    }

    public static function getFlag(string $flagName): ?string
    {
        if (!self::hasFlag($flagName)) {
            throw new \InvalidArgumentException("Flag '$flagName' not found");
        }
        return self::$flags[$flagName];
    }

    public static function getFlags(): array
    {
        return self::$flags;
    }

    public static function clearFlags(): void
    {
        self::$flags = [];
    }
}