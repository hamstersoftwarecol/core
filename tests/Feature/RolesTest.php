<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('el seeder crea los tres roles base', function () {
    $this->seed(\Database\Seeders\RolesSeeder::class);

    expect(Role::pluck('name')->all())->toEqualCanonicalizing(['super-admin', 'editor', 'usuario']);
});

test('super-admin pasa cualquier permiso via gate before', function () {
    expect(adminUser()->can('una.permision.cualquiera'))->toBeTrue();
});

test('usuario sin rol no tiene permisos', function () {
    expect(plainUser()->can('notes.access'))->toBeFalse();
});

test('userWithPermission otorga el permiso exacto', function () {
    $user = userWithPermission('notes.access');

    expect($user->can('notes.access'))->toBeTrue()
        ->and($user->can('notes.other'))->toBeFalse()
        ->and(Permission::where('name', 'notes.access')->exists())->toBeTrue();
});
