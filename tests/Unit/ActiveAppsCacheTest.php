<?php

use AppCore\Contracts\PluginState;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\Support\ActiveAppsCache;

test('reconstruye el cache desde la base y expone permisos de acceso', function () {
    config()->set('plugin-core.apps_path', base_path('tests/Fixtures/apps'));

    PluginApp::create(['slug' => 'sample', 'name' => 'Sample', 'version' => '1.0.0', 'status' => 'active']);
    PluginApp::create(['slug' => 'other', 'name' => 'Other', 'version' => '1.0.0', 'status' => 'inactive']);

    $cache = app(ActiveAppsCache::class);
    $active = $cache->rebuild();

    expect($active)->toBe(['sample'])
        ->and(is_file($cache->cacheFile()))->toBeTrue()
        ->and($cache->isActive('sample'))->toBeTrue()
        ->and($cache->isActive('other'))->toBeFalse()
        ->and($cache->accessPermissionFor('sample'))->toBe('sample.access')
        ->and($cache->accessPermissionFor('nope'))->toBeNull();
});

test('PluginState y ActiveAppsCache son el mismo singleton', function () {
    expect(app(PluginState::class))->toBe(app(ActiveAppsCache::class));
});

test('archivo ausente o corrupto devuelve estado vacio sin explotar', function () {
    $cache = app(ActiveAppsCache::class);
    file_put_contents($cache->cacheFile(), '<?php return "esto no es un array";');

    expect($cache->isActive('sample'))->toBeFalse()
        ->and($cache->accessPermissionFor('sample'))->toBeNull();

    unlink($cache->cacheFile());

    expect($cache->isActive('sample'))->toBeFalse();
});

test('los tests usan un archivo de cache propio, no el de desarrollo', function () {
    expect(config('plugin-core.cache_file'))->toBe(base_path('bootstrap/cache/active-apps.testing.php'));
});
