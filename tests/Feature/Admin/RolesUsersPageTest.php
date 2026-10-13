<?php

use App\Models\User;
use AppCore\Core\Http\Livewire\Admin\RolesPage;
use AppCore\Core\Http\Livewire\Admin\UsersPage;
use Illuminate\Support\Facades\Hash;
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

test('super-admin crea un usuario con contraseña y roles, y puede iniciar sesión', function () {
    $this->actingAs(adminUser());

    Livewire::test(UsersPage::class)
        ->call('openCreate')
        ->set('name', 'Ana López')
        ->set('email', 'ana@example.com')
        ->set('password', 'secreto-largo-123')
        ->set('password_confirmation', 'secreto-largo-123')
        ->set('newRoles', ['editor'])
        ->call('createUser')
        ->assertHasNoErrors()
        ->assertSet('mode', '');

    $user = User::where('email', 'ana@example.com')->firstOrFail();
    expect($user->hasRole('editor'))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('secreto-largo-123', $user->password))->toBeTrue();
});

test('crear usuario valida email unico, confirmacion y roles existentes', function () {
    $this->actingAs(adminUser());
    $existing = plainUser();

    Livewire::test(UsersPage::class)
        ->set('name', 'X')
        ->set('email', $existing->email)
        ->set('password', 'secreto-largo-123')
        ->set('password_confirmation', 'otra-cosa')
        ->set('newRoles', ['no-existe'])
        ->call('createUser')
        ->assertHasErrors(['email', 'password', 'newRoles.0']);
});

test('super-admin cambia la contraseña de otro usuario', function () {
    $this->actingAs(adminUser());
    $user = plainUser();

    Livewire::test(UsersPage::class)
        ->call('openPassword', $user->id)
        ->set('password', 'nueva-clave-456')
        ->set('password_confirmation', 'nueva-clave-456')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('nueva-clave-456', $user->fresh()->password))->toBeTrue();
});
