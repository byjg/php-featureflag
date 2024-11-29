<?php

namespace ByJG\FeatureFlag;

use ByJG\FeatureFlag\Attributes\FeatureFlagAttribute;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

class FeatureFlagDispatcher
{
    protected array $selectors = [];

    protected SearchOrder $searchOrder = SearchOrder::Selector;
    protected array $limitFlags = [];

    public function add(FeatureFlagSelector $selector): static
    {
        if (!isset($this->selectors[$selector->getFlagName()])) {
            $this->selectors[$selector->getFlagName()] = [];
        }

        $this->selectors[$selector->getFlagName()][] = $selector;

        return $this;
    }

    public function addClass(string $className): void
    {
        $reflection = new ReflectionClass($className);
        foreach ($reflection->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes(FeatureFlagAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
            if (count($attributes) == 0) {
                continue;
            }

            /** @var FeatureFlagAttribute $fieldAttribute */
            $fieldAttribute = $attributes[0]->newInstance();
            $featureFlag = $fieldAttribute->getFeatureFlag();
            $featureValue = $fieldAttribute->getFeatureValue();

            if (empty($featureValue)) {
                $selector = FeatureFlagSelector::whenFlagIsSet($featureFlag, [$className, $property->getName()]);
            } else {
                $selector = FeatureFlagSelector::whenFlagIs($featureFlag, $featureValue, [$className, $property->getName()]);
            }

            $this->add($selector);
        }
    }

    public function withSearchOrder(SearchOrder $searchOrder, array $flags = []): static
    {
        $this->searchOrder = $searchOrder;
        $this->limitFlags = $flags;
        if ($searchOrder == SearchOrder::Custom && empty($flags)) {
            throw new \InvalidArgumentException("You must provide the flags when using Custom search order");
        }
        if ($searchOrder != SearchOrder::Custom && !empty($flags)) {
            throw new \InvalidArgumentException("You must not provide the flags when not using Custom search order");
        }
        return $this;
    }

    public function dispatch(...$args): int
    {
        return match ($this->searchOrder) {
            SearchOrder::Selector => $this->dispatchQuerySelector(...$args),
            SearchOrder::FeatureFlags => $this->dispatchQueryingFlags(...$args),
            SearchOrder::Custom => $this->dispatchQueryCustom(...$args),
            default => 0,
        };
    }

    protected function dispatchQueryingFlags(...$args): int
    {
        $count = 0;
        foreach (FeatureFlags::getFlags() as $flagName => $flagValue) {
            if (!isset($this->selectors[$flagName])) {
                continue;
            }

            if (!FeatureFlags::hasFlag($flagName)) {
                continue;
            }

            foreach ($this->selectors[$flagName] as $selector) {
                if ($selector->isMatch($flagName, $flagValue)) {
                    $selector->invoke($args);
                    $count++;
                    if (!$selector->isContinueProcessing()) {
                        break;
                    }
                }
            }
        }

        return $count;
    }

    protected function dispatchQuerySelector(...$args): int
    {
        $count = 0;
        foreach ($this->selectors as $flagName => $selectors) {
            /** @var FeatureFlagSelector $selector */
            foreach ($selectors as $selector) {
                if (!FeatureFlags::hasFlag($flagName)) {
                    continue;
                }

                if ($selector->isMatch($flagName, FeatureFlags::getFlag($flagName))) {
                    $selector->invoke($args);
                    $count++;
                    if (!$selector->isContinueProcessing()) {
                        break;
                    }
                }
            }
        }

        return $count;
    }

    protected function dispatchQueryCustom(...$args): int
    {
        $count = 0;
        foreach ($this->limitFlags as $flagName) {
            if (!isset($this->selectors[$flagName])) {
                continue;
            }

            $selectors = $this->selectors[$flagName];
            foreach ($selectors as $selector) {
                if ($selector->isMatch($flagName, FeatureFlags::getFlag($flagName))) {
                    $selector->invoke($args);
                    $count++;
                    if (!$selector->isContinueProcessing()) {
                        break;
                    }
                }
            }
        }

        return $count;
    }
}
