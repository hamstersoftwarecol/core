<?php

namespace AppCore\Contracts;

use AppCore\Contracts\Registry\ContentType;
use AppCore\Contracts\Registry\ContentTypeRegistry;
use AppCore\Contracts\Registry\MenuItem;
use AppCore\Contracts\Registry\MenuRegistry;
use AppCore\Contracts\Registry\WidgetDefinition;
use AppCore\Contracts\Registry\WidgetRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

abstract class PluginServiceProvider extends ServiceProvider
{
    abstract protected function slug(): string;

    protected function bootPlugin(): void
    {
        //
    }

    public function boot(): void
    {
        if (! app(PluginState::class)->isActive($this->slug())) {
            return; // app inactiva: no registra absolutamente nada
        }

        try {
            $this->bootPlugin();
        } catch (\Throwable $e) {
            report($e); // una app rota nunca tumba el sitio
        }
    }

    protected function routes(string $relativeFile): void
    {
        $file = $this->packagePath($relativeFile);
        if (! is_file($file)) {
            report(new \RuntimeException("routes(): no existe {$file}"));

            return;
        }

        Route::middleware(['web', 'auth', 'app.access:'.$this->slug()])
            ->prefix('apps/'.$this->slug())
            ->name('apps.'.$this->slug().'.')
            ->group($file);

        // Las rutas con nombre añadidas en runtime no entran en el nameList
        // hasta refreshNameLookups (Laravel lo ejecuta solo en boot); sin esto,
        // Route::has()/route() fallan para apps activadas tras el boot.
        Route::getRoutes()->refreshNameLookups();
    }

    /** $route es relativo a la app: 'index' → 'apps.{slug}.index'. */
    protected function menu(string $label, string $route, ?string $permission = null, ?string $icon = null, int $order = 100): void
    {
        app(MenuRegistry::class)->add(new MenuItem(
            app: $this->slug(),
            label: $label,
            route: str_starts_with($route, 'apps.') ? $route : 'apps.'.$this->slug().'.'.$route,
            permission: $permission ?? app(PluginState::class)->accessPermissionFor($this->slug()),
            icon: $icon,
            order: $order,
        ));
    }

    /**
     * Los permisos los siembra el PluginManager desde app.json al activar; este
     * helper solo verifica que lo declarado en código coincide con el manifest.
     *
     * @param  array<int, string>  $names
     */
    protected function permissions(array $names): void
    {
        foreach ($names as $name) {
            if (! str_starts_with($name, $this->slug().'.')) {
                throw new \InvalidArgumentException("permissions(): '{$name}' debe empezar por '{$this->slug()}.'");
            }
        }
    }

    /** @param class-string $component componente Livewire */
    protected function widget(string $component, ?string $permission = null, int $order = 100): void
    {
        app(WidgetRegistry::class)->add(new WidgetDefinition(
            app: $this->slug(),
            component: $component,
            permission: $permission ?? app(PluginState::class)->accessPermissionFor($this->slug()),
            order: $order,
        ));
    }

    /** @param array<string, mixed> $config */
    protected function contentType(string $slug, string $label, array $config = []): void
    {
        if (! str_starts_with($slug, $this->slug().'.')) {
            throw new \InvalidArgumentException("contentType(): '{$slug}' debe empezar por '{$this->slug()}.'");
        }

        app(ContentTypeRegistry::class)->register(new ContentType($slug, $this->slug(), $label, $config));
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        return app(PluginConfig::class)->get($this->slug(), $key, $default);
    }

    protected function packagePath(string $relative): string
    {
        return rtrim((string) config('plugin-core.apps_path'), '/')
            .'/'.$this->slug().'/'.ltrim($relative, '/');
    }
}
