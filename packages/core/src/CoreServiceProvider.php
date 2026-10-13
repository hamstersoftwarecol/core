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

        $this->app->singleton(PluginManager::class, fn ($app) => new PluginManager(
            $app->make(ActiveAppsCache::class)
        ));
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::before(
            fn ($user, $ability) => $user->hasRole('super-admin') ? true : null
        );

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'core');

        $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');

        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('admin.apps', \AppCore\Core\Http\Livewire\Admin\AppsPage::class);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/plugin-core.php' => config_path('plugin-core.php'),
            ], 'plugin-core-config');
        }
    }
}
