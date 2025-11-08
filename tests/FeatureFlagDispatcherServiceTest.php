<?php

namespace Tests;

use ByJG\FeatureFlag\SearchOrder;
use ByJG\FeatureFlag\FeatureFlagDispatcher;
use ByJG\FeatureFlag\FeatureFlags;
use ByJG\FeatureFlag\FeatureFlagSelector;
use PHPUnit\Framework\TestCase;

class FeatureFlagDispatcherServiceTest extends TestCase
{
    public function setUp(): void
    {
        FeatureFlags::addFlag('flag1', 'value1');
        FeatureFlags::addFlag('flag2', 'value2');
        FeatureFlags::addFlag('flag3');

        SampleService::clear();
    }

    public function tearDown(): void
    {
        FeatureFlags::clearFlags();
        SampleService::clear();
    }

    public static function dataProvider()
    {
        return [
            [SearchOrder::Selector],
            [SearchOrder::FeatureFlags],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDispatchWhenFlagIs(SearchOrder $searchOrder)
    {
        $dispatcher = new FeatureFlagDispatcher();

        $dispatcher->add(FeatureFlagSelector::whenFlagIs('flag1', 'value2', [SampleService::class, 'method1']));
        $dispatcher->add(FeatureFlagSelector::whenFlagIs('flag1', 'value1', [SampleService::class, 'method2']));

        $dispatcher->withSearchOrder($searchOrder);

        $count = $dispatcher->dispatch();
        $this->assertEquals(1, $count);
        $this->assertEquals('method2', SampleService::getControl());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDispatchWhenFlagIsAndNoMatchNoDefault(SearchOrder $searchOrder)
    {
        $dispatcher = new FeatureFlagDispatcher();

        $dispatcher->add(FeatureFlagSelector::whenFlagIs('flag5', 'value2', [SampleService::class, 'method1']));
        $dispatcher->add(FeatureFlagSelector::whenFlagIs('flag5', 'value1', [SampleService::class, 'method2']));

        $count = $dispatcher->dispatch($searchOrder);

        $this->assertEquals(0, $count);
        $this->assertNull(SampleService::getControl());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDispatchWhenFlagIsAndArguments(SearchOrder $searchOrder)
    {
        $dispatcher = new FeatureFlagDispatcher();

        $dispatcher->add(FeatureFlagSelector::whenFlagIs('flag2', 'value2', [SampleService::class, 'method3']));
        $dispatcher->add(FeatureFlagSelector::whenFlagIs('flag2', 'value1', [SampleService::class, 'method4']));

        $count = $dispatcher->dispatch(10, 20);
        $this->assertEquals(1, $count);
        $this->assertEquals('method3:10:20', SampleService::getControl());

        SampleService::clear();

        $count = $dispatcher->dispatch(15, 30);
        $this->assertEquals(1, $count);
        $this->assertEquals('method3:15:30', SampleService::getControl());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDispatchWhenFlagIsSet(SearchOrder $searchOrder)
    {
        $dispatcher = new FeatureFlagDispatcher();

        $dispatcher->add(FeatureFlagSelector::whenFlagIsSet('flag1', [SampleService::class, 'method1']));
        $dispatcher->add(FeatureFlagSelector::whenFlagIsSet('flag4', [SampleService::class, 'method2']));

        $count = $dispatcher->dispatch($searchOrder);

        $this->assertEquals(1, $count);
        $this->assertEquals('method1', SampleService::getControl());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDispatchWhenFlagIsSet2(SearchOrder $searchOrder)
    {
        $dispatcher = new FeatureFlagDispatcher();

        $dispatcher->add(FeatureFlagSelector::whenFlagIsSet('flag4', [SampleService::class, 'method1']));
        $dispatcher->add(FeatureFlagSelector::whenFlagIsSet('flag3', [SampleService::class, 'method2']));

        $count = $dispatcher->dispatch($searchOrder);

        $this->assertEquals(1, $count);
        $this->assertEquals('method2', SampleService::getControl());
    }
}
