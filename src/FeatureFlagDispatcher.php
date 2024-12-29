<?php

namespace ByJG\FeatureFlag;

use ByJG\FeatureFlag\Attributes\FeatureFlagAttribute;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class FeatureFlagDispatcher
{
    protected array $selectors = [];

    protected SearchOrder $searchOrder = SearchOrder::Selector;
    protected array $limitFlags = [];

    public function add(FeatureFlagSelector|FeatureFlagSelectorSet $selector): static
    {
        if ($selector instanceof FeatureFlagSelector) {
            $this->addSelector($selector);
        } else {
            $this->addSelectorSet($selector);
        }

        return $this;
    }

    protected function addSelector(FeatureFlagSelector $selector): void
    {
        if (!isset($this->selectors[$selector->getFlagName()])) {
            $this->selectors[$selector->getFlagName()] = [];
        }

        $this->selectors[$selector->getFlagName()][] = $selector;
    }

    protected function addSelectorSet(FeatureFlagSelectorSet $selectorSet): void
    {
        $list = $selectorSet->get();

        $keys = array_keys($list);

        if (!isset($this->selectors[$keys[0]])) {
            $this->selectors[$keys[0]] = [];
        }

        $this->selectors[$keys[0]][] = $list;
    }

    /**
     * @throws ReflectionException
     */
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
        if ($searchOrder == SearchOrder::Custom && empty($flags)) {
            throw new \InvalidArgumentException("You must provide the flags when using Custom search order");
        }
        if ($searchOrder != SearchOrder::Custom && !empty($flags)) {
            throw new \InvalidArgumentException("You must not provide the flags when not using Custom search order");
        }

        if (!empty($flags)) {
            $this->limitFlags = array_filter(
                FeatureFlags::getFlags(),
                fn($key) => in_array($key, $flags),
                ARRAY_FILTER_USE_KEY
            );
        }

        return $this;
    }

    public function dispatch(...$args): int
    {
        return match ($this->searchOrder) {
            SearchOrder::Selector => $this->dispatchQuerySelector($this->selectors, false, ...$args),
            SearchOrder::FeatureFlags => $this->dispatchQueryingFlags(FeatureFlags::getFlags(), $this->selectors, false, ...$args),
            SearchOrder::Custom => $this->dispatchQueryingFlags($this->limitFlags, $this->selectors, false, ...$args),
            default => 0,
        };
    }

    protected function dispatchQueryingFlags(array $flagList, array $list, bool $isSet, ...$args): int
    {
        $count = 0;
        foreach ($flagList as $flagName => $flagValue) {
            if (!isset($list[$flagName])) {
                continue;
            }

            if (!FeatureFlags::hasFlag($flagName)) {
                continue;
            }

            foreach ($list[$flagName] as $selector) {
                $match = false;
                if (is_array($selector)) {
                    $first = array_shift($selector)[0];
                    if (!$first->isMatch($flagName, $flagValue)) {
                        continue;
                    }
                    $subCount = $this->dispatchQueryingFlags($flagList, $selector, true, ...$args);
                    $match = ($subCount === count($selector));
                    $selector = $first;
                } else {
                    $match = $selector->isMatch($flagName, $flagValue);
                }

                if ($match) {
                    if (!$isSet) {
                        $selector->invoke($args);
                    }
                    $count++;
                    if (!$selector->isContinueProcessing()) {
                        break;
                    }
                }
            }
        }

        return $count;
    }

    protected function dispatchQuerySelector(array $list, bool $isSet, ...$args): int
    {
        $count = 0;
        foreach ($list as $flagName => $selectors) {
            /** @var FeatureFlagSelector $selector */
            foreach ($selectors as $selector) {
                if (!FeatureFlags::hasFlag($flagName)) {
                    continue;
                }

                $match = false;
                if (is_array($selector)) {
                    $first = array_shift($selector)[0];
                    if (!$first->isMatch($flagName, FeatureFlags::getFlag($flagName))) {
                        continue;
                    }
                    $subCount = $this->dispatchQuerySelector($selector, true, ...$args);
                    $match = ($subCount === count($selector));
                    $selector = $first;
                } else {
                    $match = $selector->isMatch($flagName, FeatureFlags::getFlag($flagName));
                }

                if ($match) {
                    if (!$isSet) {
                        $selector->invoke($args);
                    }
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
