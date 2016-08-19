<?php

namespace Hootlex\Friendships;

use Illuminate\Support\ServiceProvider;

class FriendshipsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        if (class_exists('CreateFriendshipsTable') || class_exists('CreateFriendshipsGroupsTable')) {
            return;
        }

        $stub      = __DIR__ . '/database/migrations/';
        $target    = database_path('migrations') . '/';

        $this->publishes([
            $stub . 'create_friendships_table.php'        => $target . date('Y_m_d_His', time()) . '_create_friendships_table.php',
            $stub . 'create_friendships_groups_table.php' => $target . date('Y_m_d_His', time() + 1) . '_create_friendships_groups_table.php'
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/config/friendships.php' => config_path('friendships.php'),
        ], 'config');

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
