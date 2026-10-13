<?php

namespace AppCore\Core\Http\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UsersPage extends Component
{
    use WithPagination;

    public string $search = '';

    public function toggleRole(int $userId, string $role): void
    {
        $model = Auth::getProvider()->getModel();
        $user = $model::findOrFail($userId);
        Role::findByName($role, 'web');

        // nunca dejar el sistema sin super-admin quitándoselo a uno mismo
        abort_if($role === 'super-admin' && $user->is(Auth::user()), 422);

        $user->hasRole($role) ? $user->removeRole($role) : $user->assignRole($role);
        $this->dispatch('toast', type: 'success', message: 'Roles actualizados.');
    }

    public function render()
    {
        $model = Auth::getProvider()->getModel();

        return view('core::admin.users-list', [
            'users' => $model::query()
                ->with('roles')
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")))
                ->orderBy('name')
                ->paginate(20),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }
}
