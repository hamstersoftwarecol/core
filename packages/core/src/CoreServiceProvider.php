<?php

namespace AppCore\Core;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/plugin-core.php', 'plugin-core');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/plugin-core.php' => config_path('plugin-core.php'),
            ], 'plugin-core-config');
        }
    }
}
