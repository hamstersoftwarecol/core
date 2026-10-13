<?php

use AppCore\Contracts\Manifest;
use AppCore\Core\Exceptions\AppNotFound;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\PluginManager;

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
