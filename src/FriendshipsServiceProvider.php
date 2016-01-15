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
        if (class_exists('CreateFriendshipsTable')) {
            return;
        }

        $timestamp = date('Y_m_d_His', time());
        $stub = __DIR__.'/database/migrations/create_friendships_table.php';
        $target = database_path('migrations').'/'.$timestamp.'_create_friendships_table.php';
        $this->publishes([$stub => $target], 'migrations');
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
