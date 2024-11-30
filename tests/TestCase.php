<?php

namespace JeroenG\Packager\Tests;

use Orchestra\Testbench\TestCase as TestBench;

abstract class TestCase extends TestBench
{
    use TestHelper;

    protected const TEST_APP_TEMPLATE = __DIR__.'/../testbench/template';
    protected const TEST_APP = __DIR__.'/../testbench/laravel';

    public static function setUpBeforeClass(): void
    {
        if (! file_exists(self::TEST_APP_TEMPLATE)) {
            self::setUpLocalTestbench();
        }
        parent::setUpBeforeClass();
    }

    protected function getBasePath()
    {
        return self::TEST_APP;
    }

    /**
     * Setup before each test.
     */
    protected function setUp(): void
    {
        $this->installTestApp();
        parent::setUp();
    }

    /**
     * Tear down after each test.
     */
    protected function tearDown(): void
    {
        $this->uninstallTestApp();
        parent::tearDown();
    }

    /**
     * Tell Testbench to use this package.
     *
     * @param $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['JeroenG\Packager\PackagerServiceProvider'];
    }
}
