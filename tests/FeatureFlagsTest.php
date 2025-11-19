<?php

namespace Tests;

use ByJG\FeatureFlag\FeatureFlags;
use PHPUnit\Framework\TestCase;

class FeatureFlagsTest extends TestCase
{

    public function setUp(): void
    {
        FeatureFlags::clearFlags();
    }

    public function tearDown(): void
    {
        FeatureFlags::clearFlags();
    }


    public function testAddFromContainer()
    {
        $container = new BasicContainer();

        FeatureFlags::addFromContainer("test-key", $container);
        $this->assertTrue(FeatureFlags::hasFlag("test-key"));
        $this->assertEquals("container-key", FeatureFlags::getFlag("test-key"));

        FeatureFlags::addFromContainer("non-existant-key", $container);
        $this->assertFalse(FeatureFlags::hasFlag("non-existant-key"));
    }

    public function testAddFlag()
    {
        FeatureFlags::addFlag("flag", "test-value");

        $this->assertTrue(FeatureFlags::hasFlag("flag"));
        $this->assertEquals("test-value", FeatureFlags::getFlag("flag"));

        FeatureFlags::addFlag("flag2");
        $this->assertTrue(FeatureFlags::hasFlag("flag2"));
        $this->assertEquals("", FeatureFlags::getFlag("flag2"));
    }
}
