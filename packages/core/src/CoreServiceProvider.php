<?php

namespace AppCore\Core;

use AppCore\Contracts\PluginState;
use AppCore\Core\Support\ActiveAppsCache;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/plugin-core.php', 'plugin-core');

        $this->app->singleton(ActiveAppsCache::class, fn ($app) => new ActiveAppsCache(
            (string) $app['config']->get('plugin-core.cache_file')
        ));
        $this->app->alias(ActiveAppsCache::class, PluginState::class);
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::before(
            fn ($user, $ability) => $user->hasRole('super-admin') ? true : null
        );

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/plugin-core.php' => config_path('plugin-core.php'),
            ], 'plugin-core-config');
        }
    }
}
