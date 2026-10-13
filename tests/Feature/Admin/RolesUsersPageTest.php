<?php

use AppCore\Core\Http\Livewire\Admin\RolesPage;
use AppCore\Core\Http\Livewire\Admin\UsersPage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('roles y usuarios: solo super-admin', function (string $url) {
    $this->get($url)->assertRedirect('/login');
    $this->actingAs(plainUser())->get($url)->assertForbidden();
    $this->actingAs(adminUser())->get($url)->assertOk();
})->with(['/admin/roles', '/admin/users']);

test('crear rol, asignar permiso y borrar rol', function () {
    $this->actingAs(adminUser());
    $permission = Permission::findOrCreate('notes.access', 'web');

    Livewire::test(RolesPage::class)->set('newRole', 'redactor')->call('create')->assertHasNoErrors();
    $role = Role::findByName('redactor', 'web');

    Livewire::test(RolesPage::class)->call('toggle', $role->id, $permission->id);
    expect($role->fresh()->hasPermissionTo('notes.access'))->toBeTrue();

    Livewire::test(RolesPage::class)->call('delete', $role->id);
    expect(Role::where('name', 'redactor')->exists())->toBeFalse();
});

test('super-admin no se puede editar ni borrar', function () {
    $this->actingAs(adminUser());
    $super = Role::findByName('super-admin', 'web');

    Livewire::test(RolesPage::class)->call('delete', $super->id)->assertStatus(422);
});

test('asignar rol a usuario y no quitarse super-admin a uno mismo', function () {
    $admin = adminUser();
    $this->actingAs($admin);
    Role::findOrCreate('editor', 'web');
    $user = plainUser();

    Livewire::test(UsersPage::class)->call('toggleRole', $user->id, 'editor');
    expect($user->fresh()->hasRole('editor'))->toBeTrue();

    Livewire::test(UsersPage::class)->call('toggleRole', $admin->id, 'super-admin')->assertStatus(422);
    expect($admin->fresh()->hasRole('super-admin'))->toBeTrue();
});
