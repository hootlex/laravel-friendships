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
	    $this->publishes([
	        __DIR__.'/../src/database/migrations/' => database_path('migrations')
	    ], 'migrations');
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
