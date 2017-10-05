<?php

namespace Tests;

require __DIR__.'/helpers.php';

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup DB before each test.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set('database.default','sqlite');
    }
}
