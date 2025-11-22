<?php

namespace ByJG\FeatureFlag;

class FeatureFlagSelectorSet
{
    protected array $list = [];
    protected FeatureFlagHandlerInterface $handler;

    public function __construct(FeatureFlagHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    public function whenFlagIsSet(string $flagName): static
    {
        if (!isset($this->list[$flagName])) {
            $this->list[$flagName] = [];
        }
        $this->list[$flagName][] = FeatureFlagSelector::whenFlagIsSet($flagName, $this->handler);
        return $this;
    }

    public function whenFlagIs(string $flagName, string $flagValue): static
    {
        if (!isset($this->list[$flagName])) {
            $this->list[$flagName] = [];
        }
        $this->list[$flagName][] = FeatureFlagSelector::whenFlagIs($flagName, $flagValue, $this->handler);
        return $this;
    }

    public static function instance(FeatureFlagHandlerInterface $handler): static
    {
        return new static($handler);
    }

    public function get(): array
    {
        return $this->list;
    }
}