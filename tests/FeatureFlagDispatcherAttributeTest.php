<?php

namespace Tests;

use ByJG\FeatureFlag\SearchOrder;
use ByJG\FeatureFlag\FeatureFlagDispatcher;
use ByJG\FeatureFlag\FeatureFlags;
use ByJG\FeatureFlag\FeatureFlagSelector;
use PHPUnit\Framework\TestCase;

class FeatureFlagDispatcherAttributeTest extends TestCase
{
    public function setUp(): void
    {
        FeatureFlags::addFlag('flag1', 'value1');
        FeatureFlags::addFlag('flag2', 'value2');
        FeatureFlags::addFlag('flag3');

        SampleAttributeService::clear();
    }

    public function tearDown(): void
    {
        FeatureFlags::clearFlags();
        SampleAttributeService::clear();
    }

    public function dataProvider()
    {
        return [
            [
                SearchOrder::Selector,
                [],
                [
                    'method1',
                    'method2',
                    'method4:10:40',
                ],
            ],
            [
                SearchOrder::FeatureFlags,
                [],
                [
                    'method1',
                    'method4:10:40',
                    'method2',
                ],
            ],
            [
                SearchOrder::Custom,
                [
                    'flag1'
                ],
                [
                    'method1',
                ],
            ],
            [
                SearchOrder::Custom,
                [
                    'flag2'
                ],
                [
                    'method4:10:40',
                ],
            ],
            [
                SearchOrder::Custom,
                [
                    'flag3'
                ],
                [
                    'method2',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDispatchWhenFlagIs(SearchOrder $searchOrder, array $custom, array $expected)
    {
        $dispatcher = new FeatureFlagDispatcher();

        $dispatcher->addClass(SampleAttributeService::class);
        $dispatcher->withSearchOrder($searchOrder, $custom);

        $count = $dispatcher->dispatch(10, 40);
        $this->assertEquals(count($expected), $count);
        $this->assertEquals(
            $expected,
            SampleAttributeService::getControl()
        );
    }
}
