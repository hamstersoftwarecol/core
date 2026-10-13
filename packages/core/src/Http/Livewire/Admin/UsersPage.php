<?php

namespace AppCore\Core\Http\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UsersPage extends Component
{
    use WithPagination;

    public string $search = '';

    /** '' | 'create' | 'password' — qué panel está abierto */
    public string $mode = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @var array<int, string> roles del usuario nuevo */
    public array $newRoles = [];

    public function openCreate(): void
    {
        $this->resetForm();
        $this->mode = 'create';
    }

    public function openPassword(int $userId): void
    {
        $this->resetForm();
        $this->editingId = Auth::getProvider()->getModel()::findOrFail($userId)->getKey();
        $this->mode = 'password';
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    public function createUser(): void
    {
        $model = Auth::getProvider()->getModel();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique((new $model)->getTable(), 'email')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'newRoles' => ['array'],
            'newRoles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $user = new $model;
        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // el cast 'hashed' del modelo la cifra
            'email_verified_at' => now(),    // la crea un admin: no requiere verificar
        ])->save();

        $user->syncRoles($data['newRoles']);

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: "Usuario {$user->email} creado.");
    }

    public function updatePassword(): void
    {
        $this->validate(['password' => ['required', 'string', 'confirmed', Password::defaults()]]);

        $user = Auth::getProvider()->getModel()::findOrFail($this->editingId);
        $user->forceFill(['password' => $this->password])->save();

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: "Contraseña de {$user->email} actualizada.");
    }

    private function resetForm(): void
    {
        $this->reset(['mode', 'editingId', 'name', 'email', 'password', 'password_confirmation', 'newRoles']);
        $this->resetValidation();
    }

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
            'editing' => $this->editingId ? $model::find($this->editingId) : null,
        ]);
    }
}
