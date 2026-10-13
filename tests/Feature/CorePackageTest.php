<?php

use AppCore\Core\CoreServiceProvider;

test('el paquete core esta registrado y su config se fusiona', function () {
    expect(config('plugin-core.apps_path'))->toBe(base_path('apps'))
        ->and(config('plugin-core.cache_file'))->toBe(app()->bootstrapPath('cache/active-apps.php'))
        ->and(app()->getProviders(CoreServiceProvider::class))->not->toBeEmpty();
});
