<?php

namespace AppCore\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MakeAdminCommand extends Command
{
    protected $signature = 'core:make-admin {email : Email de un usuario ya registrado}';

    protected $description = 'Asigna el rol super-admin a un usuario existente';

    public function handle(): int
    {
        $model = Auth::getProvider()->getModel();
        $user = $model::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No existe un usuario con ese email. Regístralo primero en /register.');

            return self::FAILURE;
        }

        Role::findOrCreate('super-admin', 'web');
        $user->assignRole('super-admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("{$user->email} ahora es super-admin.");

        return self::SUCCESS;
    }
}
