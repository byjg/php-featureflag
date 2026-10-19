<?php

namespace Tests;

use ByJG\FeatureFlag\SearchOrder;
use ByJG\FeatureFlag\FeatureFlagDispatcher;
use ByJG\FeatureFlag\FeatureFlags;
use ByJG\FeatureFlag\FeatureFlagSelector;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function dataProvider()
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

    #[DataProvider('dataProvider')]
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

    public static function zeroValueProvider(): array
    {
        return [
            'flag4 is "1"' => ['1', false],
            'flag4 is "0"' => ['0', true],
        ];
    }

    /**
     * An attribute value of '0' is a value to match, not "flag is set"
     */
    #[DataProvider('zeroValueProvider')]
    public function testAttributeValueZeroMatchesOnlyThatValue(string $flagValue, bool $expected)
    {
        FeatureFlags::addFlag('flag4', $flagValue);

        $dispatcher = new FeatureFlagDispatcher();
        $dispatcher->addClass(SampleAttributeService::class);
        $dispatcher->dispatch(10, 40);

        $this->assertSame($expected, in_array('method5', SampleAttributeService::getControl()));
    }
}
