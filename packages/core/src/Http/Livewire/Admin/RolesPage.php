<?php

namespace AppCore\Core\Http\Livewire\Admin;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPage extends Component
{
    public string $newRole = '';

    public ?string $flash = null;

    public function create(): void
    {
        $this->validate(['newRole' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/', 'unique:roles,name']]);
        Role::create(['name' => $this->newRole, 'guard_name' => 'web']);
        $this->newRole = '';
        $this->flash = 'Rol creado.';
    }

    public function toggle(int $roleId, int $permissionId): void
    {
        $role = Role::findOrFail($roleId);
        abort_if($role->name === 'super-admin', 422);
        $permission = Permission::findOrFail($permissionId);

        $role->hasPermissionTo($permission)
            ? $role->revokePermissionTo($permission)
            : $role->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function delete(int $roleId): void
    {
        $role = Role::findOrFail($roleId);
        abort_if($role->name === 'super-admin', 422);
        $role->delete();
        $this->flash = 'Rol eliminado.';
    }

    public function render()
    {
        return view('core::admin.roles-list', [
            'roles' => Role::with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }
}
