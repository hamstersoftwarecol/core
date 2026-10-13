<?php

namespace AppCore\Contracts;

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

    protected function packagePath(string $relative): string
    {
        return rtrim((string) config('plugin-core.apps_path'), '/')
            .'/'.$this->slug().'/'.ltrim($relative, '/');
    }
}
