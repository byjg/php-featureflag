<?php

namespace ByJG\FeatureFlag;

class FeatureFlagSelectorSet
{
    protected array $list = [];
    protected \Closure|array $callable;

    public function __construct(\Closure|array $callable)
    {
        $this->callable = $callable;
    }

    public function whenFlagIsSet(string $flagName): static
    {
        if (!isset($this->list[$flagName])) {
            $this->list[$flagName] = [];
        }
        $this->list[$flagName][] = FeatureFlagSelector::whenFlagIsSet($flagName, $this->callable);
        return $this;
    }

    public function whenFlagIs(string $flagName, string $flagValue): static
    {
        if (!isset($this->list[$flagName])) {
            $this->list[$flagName] = [];
        }
        $this->list[$flagName][] = FeatureFlagSelector::whenFlagIs($flagName, $flagValue, $this->callable);
        return $this;
    }

    public static function instance(\Closure|array $callable): static
    {
        return new FeatureFlagSelectorSet($callable);
    }

    public function get(): array
    {
        return $this->list;
    }
}