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

            $handler = new StaticMethodHandler($className, $property->getName());

            if (empty($featureValue)) {
                $selector = FeatureFlagSelector::whenFlagIsSet($featureFlag, $handler);
            } else {
                $selector = FeatureFlagSelector::whenFlagIs($featureFlag, $featureValue, $handler);
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
            SearchOrder::Selector => $this->dispatchQuerySelector($this->selectors, true, ...$args),
            SearchOrder::FeatureFlags => $this->dispatchQueryingFlags(FeatureFlags::getFlags(), $this->selectors, true, ...$args),
            SearchOrder::Custom => $this->dispatchQueryingFlags($this->limitFlags, $this->selectors, true, ...$args),
            default => 0,
        };
    }

    protected function dispatchQueryingFlags(array $flagList, array $selectorList, bool $invoke, ...$args): int
    {
        $count = 0;
        foreach ($flagList as $flagName => $flagValue) {
            if (!isset($selectorList[$flagName])) {
                continue;
            }

            if (!FeatureFlags::hasFlag($flagName)) {
                continue;
            }

            foreach ($selectorList[$flagName] as $selector) {
                $result = $this->match($selector, $flagName, $flagValue, $flagList, $invoke, ...$args);
                $count += ($result < 0 ? -$result : $result);
                if ($result < 0) {
                    break;
                }
            }
        }

        return $count;
    }

    protected function dispatchQuerySelector(array $selectorList, bool $invoke, ...$args): int
    {
        $count = 0;
        foreach ($selectorList as $flagName => $selectors) {
            /** @var FeatureFlagSelector $selector */
            foreach ($selectors as $selector) {
                if (!FeatureFlags::hasFlag($flagName)) {
                    continue;
                }

                $result = $this->match($selector, $flagName, FeatureFlags::getFlag($flagName), [], $invoke, ...$args);
                $count += ($result < 0 ? -$result : $result);
                if ($result < 0) {
                    break;
                }
            }
        }

        return $count;
    }

    protected function match(FeatureFlagSelector|array $selector, string $flagName, mixed $flagValue, array $flagList, bool $invoke, mixed ...$args): int
    {
        if (is_array($selector)) {
            return $this->matchSelectorArray($selector, $flagName, $flagValue, $flagList, $invoke, ...$args);
        }

        return $this->matchSelector($selector, $flagName, $flagValue, $invoke, ...$args);
    }

    protected function matchSelector(FeatureFlagSelector $selector, string $flagName, mixed $flagValue, bool $invoke, mixed ...$args): int
    {
        if ($selector->isMatch($flagName, $flagValue)) {
            if ($invoke) {
                $selector->invoke(...$args);
            }
            if (!$selector->isContinueProcessing()) {
                return -1;
            }
            return 1;
        }

        return 0;
    }

    protected function matchSelectorArray(array $selector, string $flagName, mixed $flagValue, array $flagList, bool $invoke, mixed ...$args): int
    {
        $first = array_shift($selector)[0];
        if (!$first->isMatch($flagName, $flagValue)) {
            return 0;
        }
        if (empty($flagList)) {
            $subCount = $this->dispatchQuerySelector($selector, false);
        } else {
            $subCount = $this->dispatchQueryingFlags($flagList, $selector, false);
        }

        if ($subCount === count($selector)) {
            return $this->matchSelector($first, $flagName, $flagValue, $invoke, ...$args);
        }

        return 0;
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
                    $selector->invoke(...$args);
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
