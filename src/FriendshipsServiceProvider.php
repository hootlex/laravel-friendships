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

        $config = include __DIR__.'/config/friendships.php';

        if (class_exists('CreateFriendshipsTable') || class_exists('CreateFriendFriendshipGroupsTable')) {
            return;
        }

        $stub      = __DIR__.'/database/migrations/';
        $target    = database_path('migrations').'/';

        $migrations = [];
        $migrations[$stub.'create_friendships_table.php'] = $target.date('Y_m_d_His', time()).'_create_friendships_table.php';

        if (isset($config['groups']) && !empty($config['groups'])) {
            $migrations[$stub.'create_friend_friendship_groups_table.php'] = $target.date('Y_m_d_His', time()+1).'_create_friend_friendship_groups_table.php';
        }

        $this->publishes($migrations, 'migrations');

        $this->publishes([
            __DIR__.'/config/friendships.php' => config_path('friendships.php'),
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
