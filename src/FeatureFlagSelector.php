<?php

namespace ByJG\FeatureFlag;

class FeatureFlagSelector
{
    protected function __construct(
        protected FeatureFlagHandlerInterface $handler,
        protected string $flagName,
        protected bool|string $flagValue,
        protected bool $continueProcessing = true
    ) {
    }

    public static function whenFlagIsSet(string $flagName, FeatureFlagHandlerInterface $handler): static
    {
        return new static($handler, $flagName, true);
    }

    public static function whenFlagIs(string $flagName, string $flagValue, FeatureFlagHandlerInterface $handler): static
    {
        return new static($handler, $flagName, $flagValue);
    }

    public function stopPropagation(): static
    {
        $this->continueProcessing = false;
        return $this;
    }

    public function isContinueProcessing(): bool
    {
        return $this->continueProcessing;
    }


    public function getFlagName(): string
    {
        return $this->flagName;
    }

    public function getFlagValue(): bool|string
    {
        return $this->flagValue;
    }

    public function getHandler(): FeatureFlagHandlerInterface
    {
        return $this->handler;
    }

    public function invoke(...$args): mixed
    {
        return $this->handler->execute(...$args);
    }

    public function isMatch(string $flagName, ?string $flagValue = null): bool
    {
        if ($this->flagName !== $flagName) {
            return false;
        }

        if ($this->flagValue === true) {
            return true;
        }

        if ($flagValue === null) {
            return true;
        }

        if ($this->flagValue !== $flagValue) {
            return false;
        }

        return true;
    }
}