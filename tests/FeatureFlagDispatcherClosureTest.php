<?php

namespace Tests;

use ByJG\FeatureFlag\SearchOrder;
use ByJG\FeatureFlag\FeatureFlagDispatcher;
use ByJG\FeatureFlag\FeatureFlags;
use ByJG\FeatureFlag\FeatureFlagSelector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FeatureFlagDispatcherClosureTest extends TestCase
{
    public function setUp(): void
    {
        FeatureFlags::addFlag('flag1', 'value1');
        FeatureFlags::addFlag('flag2', 'value2');
        FeatureFlags::addFlag('flag3');
    }

    public function tearDown(): void
    {
        FeatureFlags::clearFlags();
    }

    public static function dataProvider()
    {
        return [
            [SearchOrder::Selector],
            [SearchOrder::FeatureFlags],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testDispatchWhenFlagIs(SearchOrder $searchOrder)
    {
        $dispatcher = new FeatureFlagDispatcher();

        $control = null;

        $dispatcher->add(FeatureFlagSelector::whenFlagIs('flag1', 'value2', function () use (&$control) {
            $control = 'flag1:value2';
        }));

        $dispatcher->add(FeatureFlagSelector::whenFlagIs('flag1', 'value1', function () use (&$control) {
            $control = 'flag1:value1';
        }));

        $dispatcher->withSearchOrder($searchOrder);

        $count = $dispatcher->dispatch();
        $this->assertEquals(1, $count);
        $this->assertEquals('flag1:value1', $control);
    }

    #[DataProvider('dataProvider')]
    public function testDispatchWhenFlagIsAndNoMatchNoDefault(SearchOrder $searchOrder)
    {
        $dispatcher = new FeatureFlagDispatcher();

        $control = null;

        $dispatcher->add(FeatureFlagSelector::whenFlagIs('flag5', 'value2', function () use (&$control) {
            $control = 'flag1:value2';
        }));

        $dispatcher->add(FeatureFlagSelector::whenFlagIs('flag5', 'value1', function () use (&$control) {
            $control = 'flag1:value1';
        }));

        $dispatcher->withSearchOrder($searchOrder);

        $count = $dispatcher->dispatch();
        $this->assertEquals(0, $count);
        $this->assertNull($control);
    }

    #[DataProvider('dataProvider')]
    public function testDispatchWhenFlagIsAndArguments(SearchOrder $searchOrder)
    {
        $dispatcher = new FeatureFlagDispatcher();

        $control = null;

        $dispatcher->add(FeatureFlagSelector::whenFlagIs('flag2', 'value2', function ($arg1, $arg2) use (&$control) {
            $control = "flag2:value2:$arg1:$arg2";
        }));

        $dispatcher->add(FeatureFlagSelector::whenFlagIs('flag2', 'value1', function ($arg1, $arg2) use (&$control) {
            $control = "flag2:value1:$arg1:$arg2";
        }));

        $dispatcher->withSearchOrder($searchOrder);

        $count = $dispatcher->dispatch(10, 20);
        $this->assertEquals(1, $count);
        $this->assertEquals('flag2:value2:10:20', $control);

        $control = null;

        $count = $dispatcher->dispatch(15, 30);
        $this->assertEquals(1, $count);
        $this->assertEquals('flag2:value2:15:30', $control);
    }

    #[DataProvider('dataProvider')]
    public function testDispatchWhenFlagIsSet(SearchOrder $searchOrder)
    {
        $dispatcher = new FeatureFlagDispatcher();

        $control = null;

        $dispatcher->add(FeatureFlagSelector::whenFlagIsSet('flag1', function () use (&$control) {
            $control = 'flag1';
        }));

        $dispatcher->add(FeatureFlagSelector::whenFlagIsSet('flag4', function () use (&$control) {
            $control = 'flag4';
        }));

        $dispatcher->withSearchOrder($searchOrder);

        $count = $dispatcher->dispatch();
        $this->assertEquals(1, $count);
        $this->assertEquals('flag1', $control);
    }

    #[DataProvider('dataProvider')]
    public function testDispatchWhenFlagIsSet2(SearchOrder $searchOrder)
    {
        $dispatcher = new FeatureFlagDispatcher();

        $control = null;

        $dispatcher->add(FeatureFlagSelector::whenFlagIsSet('flag4', function () use (&$control) {
            $control = 'flag4';
        }));

        $dispatcher->add(FeatureFlagSelector::whenFlagIsSet('flag3', function () use (&$control) {
            $control = 'flag3';
        }));

        $count = $dispatcher->dispatch($searchOrder);

        $this->assertEquals(1, $count);
        $this->assertEquals('flag3', $control);
    }
}
