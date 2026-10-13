<?php

use AppCore\Contracts\Exceptions\InvalidManifest;
use AppCore\Contracts\Manifest;

function fixture_manifest_path(string $app): string
{
    return base_path("tests/Fixtures/apps/{$app}/app.json");
}

test('parsea un manifest valido', function () {
    $m = Manifest::fromFile(fixture_manifest_path('sample'));

    expect($m->slug())->toBe('sample')
        ->and($m->name())->toBe('Sample App')
        ->and($m->version())->toBe('1.0.0')
        ->and($m->permissions())->toBe(['sample.access'])
        ->and($m->accessPermission())->toBe('sample.access')
        ->and($m->requiredApps())->toBe([])
        ->and($m->phpConstraint())->toBe('>=8.3')
        ->and($m->laravelConstraint())->toBe('^12.0')
        ->and($m->migrationsPath())->toBe(base_path('tests/Fixtures/apps/sample/database/migrations'));
});

test('el permiso de acceso por defecto se agrega a permissions', function () {
    $m = Manifest::fromArray(
        ['slug' => 'demo', 'name' => 'Demo', 'version' => '1.0.0', 'permissions' => ['demo.write']],
        base_path('tests')
    );

    expect($m->accessPermission())->toBe('demo.access')
        ->and($m->permissions())->toContain('demo.access')
        ->and($m->permissions())->toContain('demo.write');
});

test('rechaza un manifest sin campos obligatorios', function () {
    Manifest::fromArray(['name' => 'X', 'version' => '1.0.0'], base_path());
})->throws(InvalidManifest::class, "app.json: falta el campo obligatorio 'slug'");

test('rechaza un slug invalido (previene path traversal)', function () {
    Manifest::fromArray(['slug' => '../etc', 'name' => 'X', 'version' => '1.0.0'], base_path());
})->throws(InvalidManifest::class, 'app.json: slug inválido \'../etc\' (solo minúsculas, números y guiones)');

test('rechaza permisos sin prefijo del slug', function () {
    Manifest::fromArray(
        ['slug' => 'demo', 'name' => 'Demo', 'version' => '1.0.0', 'permissions' => ['admin.delete']],
        base_path()
    );
})->throws(InvalidManifest::class, "app.json: el permiso 'admin.delete' debe empezar por 'demo.'");

test('rechaza json corrupto', function () {
    Manifest::fromFile(fixture_manifest_path('not-a-real-app'));
})->throws(InvalidManifest::class);
