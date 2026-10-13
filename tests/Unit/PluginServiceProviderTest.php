<?php

use AppCore\Contracts\PluginServiceProvider;
use AppCore\Core\PluginManager;
use AppCore\Notes\NotesServiceProvider;
use Illuminate\Support\Facades\Route;

test('provider de una app inactiva no registra rutas', function () {
    $provider = new NotesServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    expect(Route::has('apps.notes.hello'))->toBeFalse();
});

test('provider de una app activa registra ruta con prefijo, nombre y middleware', function () {
    app(PluginManager::class)->activate('notes');

    $provider = new NotesServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    expect(Route::has('apps.notes.hello'))->toBeTrue();

    $route = Route::getRoutes()->getByName('apps.notes.hello');
    expect($route->uri())->toBe('apps/notes/hello')
        ->and($route->middleware())->toContain('app.access:notes')
        ->and($route->middleware())->toContain('web')
        ->and($route->middleware())->toContain('auth');
});

test('un bootPlugin que revienta no propaga la excepcion', function () {
    $provider = new class($this->app) extends PluginServiceProvider
    {
        protected function slug(): string
        {
            return 'no-existe';
        }

        protected function bootPlugin(): void
        {
            throw new RuntimeException('fallo deliberado');
        }
    };

    $provider->register();
    $provider->boot(); // no debe lanzar

    expect(true)->toBeTrue();
});
