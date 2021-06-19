<?php

namespace Tests;

// require __DIR__ . '/helpers.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestBase extends BaseTestCase
{
    use CreatesApplication, DatabaseMigrations;

    public function setUp() : void
    {
        parent::setUp();
        $this->enableForeignKeys();
        $this->setConfigValues($this->app);
        $this->runMigrations();
    }

    /**
     * Enables foreign keys.
     *
     * @return void
     */
    public function enableForeignKeys() : void
    {
        $db = app()->make('db');
        $db->getSchemaBuilder()->enableForeignKeyConstraints();
    }

    /**
     * Set config values.
     *
     * @param $app
     * @return void
     */
    public function setConfigValues($app) : void
    {
        $app['config']->set('friendships.tables.fr_pivot', 'friendships');
        $app['config']->set('friendships.tables.fr_groups_pivot', 'user_friendship_groups');
    }

    /**
     * Migrates tables.
     *
     * @return void
     */
    public function runMigrations() : void
    {
        include_once __DIR__ . '/../src/database/migrations/create_friendships_table.php';
        include_once __DIR__ . '/../src/database/migrations/create_friendships_groups_table.php';
        $this->artisan('migrate');
        (new \CreateFriendshipsTable())->up();
        (new \CreateFriendshipsGroupsTable())->up();
    }
}
