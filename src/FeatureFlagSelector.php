<?php

namespace ByJG\FeatureFlag;

class FeatureFlagSelector
{
    protected bool $createInstance = false;

    protected function __construct(
        protected \Closure|array $callable,
        protected string $flagName,
        protected bool|string $flagValue,
        protected bool $continueProcessing = true
    ) {
        if (is_array($this->callable)) {
            if (count($this->callable) !== 2) {
                throw new \InvalidArgumentException("The callable must be a valid callable");
            }

            if (!is_object($this->callable[0]) && !class_exists($this->callable[0])) {
                throw new \InvalidArgumentException("The class must be a valid class");
            }

            if (!method_exists($this->callable[0], $this->callable[1])) {
                throw new \InvalidArgumentException("The method must be a valid method");
            }

            if (is_string($this->callable[0])) {
                $this->createInstance = true;
            }
        }
    }

    public static function whenFlagIsSet(string $flagName, \Closure|array $callable): static
    {
        return new static($callable, $flagName, true);
    }

    public static function whenFlagIs(string $flagName, string $flagValue, \Closure|array $callable): static
    {
        return new static($callable, $flagName, $flagValue);
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

    public function getCallable(): \Closure|array
    {
        return $this->callable;
    }

    /** @psalm-suppress UndefinedMethod When create instance is true, $this->callable is always an array */
    public function invoke(...$args): mixed
    {
        if ($this->createInstance) {
            $instance = new $this->callable[0];
            return call_user_func_array([$instance, $this->callable[1]], ...$args);
        }

        return call_user_func_array($this->callable, ...$args);
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