<?php

namespace Ab\ApiGenerator\Tests;

use Orchestra\Testbench\TestCase;
use Ab\ApiGenerator\ApiGeneratorServiceProvider;

class ExampleTest extends TestCase
{
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }

    protected function getPackageProviders($app)
    {
        return [ApiGeneratorServiceProvider::class];
    }
}
