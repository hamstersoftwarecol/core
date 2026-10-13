<?php

namespace AppCore\Core;

use AppCore\Contracts\PluginConfig;
use AppCore\Contracts\PluginState;
use AppCore\Contracts\Registry\ContentTypeRegistry as ContentTypeRegistryContract;
use AppCore\Contracts\Registry\MenuRegistry as MenuRegistryContract;
use AppCore\Contracts\Registry\WidgetRegistry as WidgetRegistryContract;
use AppCore\Core\Console\AppsActivateCommand;
use AppCore\Core\Console\AppsDeactivateCommand;
use AppCore\Core\Console\AppsListCommand;
use AppCore\Core\Console\AppsVerifyCommand;
use AppCore\Core\Http\Livewire\Admin\AppConfigPage;
use AppCore\Core\Http\Livewire\Admin\AppsPage;
use AppCore\Core\Http\Livewire\Admin\RolesPage;
use AppCore\Core\Http\Livewire\Admin\UsersPage;
use AppCore\Core\Registry\ContentTypeRegistry;
use AppCore\Core\Registry\MenuRegistry;
use AppCore\Core\Registry\WidgetRegistry;
use AppCore\Core\Support\ActiveAppsCache;
use AppCore\Core\Support\DatabasePluginConfig;
use AppCore\Core\View\AppMenu;
use AppCore\Core\View\DashboardWidgets;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/plugin-core.php', 'plugin-core');

        $this->app->singleton(ActiveAppsCache::class, fn ($app) => new ActiveAppsCache(
            (string) $app['config']->get('plugin-core.cache_file')
        ));
        $this->app->alias(ActiveAppsCache::class, PluginState::class);

        $this->app->singleton(MenuRegistry::class);
        $this->app->alias(MenuRegistry::class, MenuRegistryContract::class);
        $this->app->singleton(WidgetRegistry::class);
        $this->app->alias(WidgetRegistry::class, WidgetRegistryContract::class);
        $this->app->singleton(ContentTypeRegistry::class);
        $this->app->alias(ContentTypeRegistry::class, ContentTypeRegistryContract::class);
        $this->app->singleton(DatabasePluginConfig::class);
        $this->app->alias(DatabasePluginConfig::class, PluginConfig::class);

        $this->app->singleton(PluginManager::class, fn ($app) => new PluginManager(
            $app->make(ActiveAppsCache::class)
        ));
    }

    public function boot(): void
    {
        Gate::before(
            fn ($user, $ability) => $user->hasRole('super-admin') ? true : null
        );

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'core');

        $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');

        if (class_exists(Livewire::class)) {
            Livewire::component('admin.apps', AppsPage::class);
            Livewire::component('admin.app-config', AppConfigPage::class);
            Livewire::component('admin.roles', RolesPage::class);
            Livewire::component('admin.users', UsersPage::class);
        }

        Blade::component('core-app-menu', AppMenu::class);
        Blade::component('core-dashboard-widgets', DashboardWidgets::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                AppsListCommand::class,
                AppsActivateCommand::class,
                AppsDeactivateCommand::class,
                AppsVerifyCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/plugin-core.php' => config_path('plugin-core.php'),
            ], 'plugin-core-config');
        }
    }
}
