<?php

use AppCore\Contracts\Manifest;
use AppCore\Core\Exceptions\ActivationFailed;
use AppCore\Core\Exceptions\AppNotFound;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\PluginManager;
use AppCore\Core\Support\ActiveAppsCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

function use_fixture_apps_path(): void
{
    config()->set('plugin-core.apps_path', base_path('tests/Fixtures/apps'));
}

test('descubre apps por su app.json en disco', function () {
    use_fixture_apps_path();

    $found = app(PluginManager::class)->discover();

    expect($found->keys()->all())->toEqualCanonicalizing(
        ['sample', 'sample-broken', 'sample-req-laravel', 'sample-req-app']
    )->and($found['sample'])->toBeInstanceOf(Manifest::class);
});

test('sync crea o actualiza filas sin tocar el estado', function () {
    use_fixture_apps_path();
    PluginApp::create(['slug' => 'sample', 'name' => 'Viejo', 'version' => '0.9.0', 'status' => 'active']);

    app(PluginManager::class)->sync();

    $row = PluginApp::where('slug', 'sample')->first();
    expect($row->name)->toBe('Sample App')
        ->and($row->version)->toBe('1.0.0')
        ->and($row->status)->toBe('active') // el estado existente se conserva
        ->and(PluginApp::where('slug', 'sample-broken')->exists())->toBeTrue();
});

test('manifestFor devuelve el manifest', function () {
    use_fixture_apps_path();

    expect(app(PluginManager::class)->manifestFor('sample')->slug())->toBe('sample');
});

test('manifestFor lanza AppNotFound si no existe', function () {
    use_fixture_apps_path();

    app(PluginManager::class)->manifestFor('no-existe');
})->throws(AppNotFound::class, 'App "no-existe" no encontrada');

test('manifestFor rechaza slugs con intento de path traversal', function () {
    use_fixture_apps_path();

    app(PluginManager::class)->manifestFor('../../.env');
})->throws(AppNotFound::class);

test('activar ejecuta migraciones, crea permisos, marca activa y actualiza el cache', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);

    $manager->activate('sample');

    expect(Schema::hasTable('sample_items'))->toBeTrue()
        ->and(Permission::where('name', 'sample.access')->exists())->toBeTrue();

    $row = PluginApp::where('slug', 'sample')->first();
    expect($row->status)->toBe(PluginApp::STATUS_ACTIVE)
        ->and($row->activated_at)->not->toBeNull()
        ->and($row->last_error)->toBeNull();

    $cache = app(ActiveAppsCache::class);
    expect($cache->isActive('sample'))->toBeTrue()
        ->and($cache->accessPermissionFor('sample'))->toBe('sample.access');
});

test('no activa si la version de Laravel no cumple el requisito', function () {
    use_fixture_apps_path();

    app(PluginManager::class)->activate('sample-req-laravel');
})->throws(ActivationFailed::class, 'Requiere Laravel ^99.0');

test('no activa si una app requerida no esta activa', function () {
    use_fixture_apps_path();

    app(PluginManager::class)->activate('sample-req-app');
})->throws(ActivationFailed::class, 'Requiere la app "sample" activa');

test('los requisitos fallidos no cambian el estado', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);
    $manager->sync(); // la UI sincroniza el listado antes de activar

    try {
        $manager->activate('sample-req-app');
    } catch (ActivationFailed) {
    }

    $row = PluginApp::where('slug', 'sample-req-app')->first();
    expect($row->status)->toBe(PluginApp::STATUS_INACTIVE)
        ->and(app(ActiveAppsCache::class)->isActive('sample-req-app'))->toBeFalse();
});

test('migracion rota deja la app en error, fuera del cache, con mensaje reintetable', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);

    expect(fn () => $manager->activate('sample-broken'))
        ->toThrow(ActivationFailed::class);

    $row = PluginApp::where('slug', 'sample-broken')->first();
    expect($row->status)->toBe(PluginApp::STATUS_ERROR)
        ->and($row->last_error)->toContain('boom')
        ->and(Schema::hasTable('broken_table'))->toBeFalse()
        ->and(app(ActiveAppsCache::class)->isActive('sample-broken'))->toBeFalse();
});

test('reintentar la activacion es posible y vuelve a fallar limpiamente', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);

    try {
        $manager->activate('sample-broken');
    } catch (ActivationFailed) {
    }
    expect(fn () => $manager->activate('sample-broken'))->toThrow(ActivationFailed::class);

    expect(PluginApp::where('slug', 'sample-broken')->value('status'))->toBe(PluginApp::STATUS_ERROR);
});

test('desactivar oculta la app y conserva los datos', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);
    $manager->activate('sample');
    DB::table('sample_items')->insert(['name' => 'registro vivo', 'created_at' => now(), 'updated_at' => now()]);

    $manager->deactivate('sample');

    expect(Schema::hasTable('sample_items'))->toBeTrue()
        ->and(DB::table('sample_items')->count())->toBe(1)
        ->and(PluginApp::where('slug', 'sample')->value('status'))->toBe(PluginApp::STATUS_INACTIVE)
        ->and(app(ActiveAppsCache::class)->isActive('sample'))->toBeFalse()
        ->and(Permission::where('name', 'sample.access')->exists())->toBeFalse();
});

test('desactivar una app no instalada lanza AppNotFound', function () {
    use_fixture_apps_path();

    app(PluginManager::class)->deactivate('no-existe');
})->throws(AppNotFound::class);

test('reactivar despues de desactivar restaura permisos', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);

    $manager->activate('sample');
    $manager->deactivate('sample');
    $manager->activate('sample');

    expect(Permission::where('name', 'sample.access')->exists())->toBeTrue()
        ->and(app(ActiveAppsCache::class)->isActive('sample'))->toBeTrue();
});

test('desinstalar revierte migraciones, borra la fila y limpia el cache', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);
    $manager->activate('sample');

    $manager->uninstall('sample');

    expect(Schema::hasTable('sample_items'))->toBeFalse()
        ->and(PluginApp::where('slug', 'sample')->exists())->toBeFalse()
        ->and(app(ActiveAppsCache::class)->isActive('sample'))->toBeFalse()
        ->and(Permission::where('name', 'sample.access')->exists())->toBeFalse();
});

test('desinstalar una app activa primero la desactiva', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);
    $manager->activate('sample');

    $manager->uninstall('sample');

    expect(Schema::hasTable('sample_items'))->toBeFalse()
        ->and(PluginApp::where('slug', 'sample')->exists())->toBeFalse();
});
