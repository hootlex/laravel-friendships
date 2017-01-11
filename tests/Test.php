<?php

abstract class Test extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../vendor/laravel/laravel/bootstrap/app.php';

        if ( ! $app->hasBeenBootstrapped())
        {
            $app->bootstrapWith(
                [
                    'Illuminate\Foundation\Bootstrap\DetectEnvironment',
                    'Illuminate\Foundation\Bootstrap\LoadConfiguration',
                    'Illuminate\Foundation\Bootstrap\ConfigureLogging',
                    'Illuminate\Foundation\Bootstrap\RegisterFacades',
                    'Illuminate\Foundation\Bootstrap\SetRequestForConsole',
                    'Illuminate\Foundation\Bootstrap\RegisterProviders',
                    'Illuminate\Foundation\Bootstrap\BootProviders',
                ]
            );
        }

        return $app;
    }
}
