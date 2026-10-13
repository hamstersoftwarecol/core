<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o email"
            class="w-full max-w-sm rounded-lg border-zinc-300 text-sm dark:border-white/10 dark:bg-white/5">

        <x-primary-button type="button" wire:click="openCreate">
            <flux:icon.user-plus class="size-4" /> Nuevo usuario
        </x-primary-button>
    </div>

    @if ($mode)
        <div class="rounded-2xl bg-white p-6 shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $mode === 'create' ? 'Nuevo usuario' : 'Cambiar contraseña' }}</h2>
                    <p class="mt-1 text-sm text-zinc-500">
                        @if ($mode === 'create')
                            El usuario podrá entrar de inmediato con este email y contraseña.
                        @else
                            Nueva contraseña para <strong>{{ $editing?->email }}</strong>.
                        @endif
                    </p>
                </div>
                <button type="button" wire:click="closeForm" class="text-zinc-400 hover:text-zinc-600" aria-label="Cerrar">
                    <flux:icon.x-mark class="size-5" />
                </button>
            </div>

            <form wire:submit="{{ $mode === 'create' ? 'createUser' : 'updatePassword' }}" class="grid gap-5 sm:grid-cols-2">
                @if ($mode === 'create')
                    <div>
                        <x-input-label for="u-name" :value="__('Name')" />
                        <x-text-input wire:model="name" id="u-name" class="mt-2" type="text" required autocomplete="off" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="u-email" :value="__('Email')" />
                        <x-text-input wire:model="email" id="u-email" class="mt-2" type="email" required autocomplete="off" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                @endif

                <div>
                    <x-input-label for="u-password" :value="__('Password')" />
                    <x-text-input wire:model="password" id="u-password" class="mt-2" type="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="u-password-confirmation" :value="__('Confirm Password')" />
                    <x-text-input wire:model="password_confirmation" id="u-password-confirmation" class="mt-2" type="password" required autocomplete="new-password" />
                </div>

                @if ($mode === 'create')
                    <div class="sm:col-span-2">
                        <x-input-label value="Roles" />
                        <div class="mt-2 flex flex-wrap gap-4">
                            @foreach ($roles as $role)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model="newRoles" value="{{ $role }}" class="rounded border-zinc-300 text-indigo-600 dark:border-white/20 dark:bg-white/5">
                                    {{ $role }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('newRoles.*')" class="mt-2" />
                    </div>
                @endif

                <div class="flex gap-2 sm:col-span-2">
                    <x-primary-button wire:loading.attr="disabled">{{ $mode === 'create' ? 'Crear usuario' : 'Guardar contraseña' }}</x-primary-button>
                    <x-secondary-button wire:click="closeForm">{{ __('Cancel') }}</x-secondary-button>
                </div>
            </form>
        </div>
    @endif

    <div class="overflow-x-auto rounded-2xl bg-white shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-white/10">
            <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Usuario</th>
                    <th class="px-4 py-3 text-left font-medium">Roles</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                @foreach ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-3">
                                @foreach ($roles as $role)
                                    <label class="flex items-center gap-1 text-xs">
                                        <input type="checkbox" wire:click="toggleRole({{ $user->id }}, '{{ $role }}')"
                                            @checked($user->roles->contains('name', $role))
                                            class="rounded border-zinc-300 text-indigo-600 dark:border-white/20 dark:bg-white/5">
                                        {{ $role }}
                                    </label>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="openPassword({{ $user->id }})"
                                class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold ring-1 ring-zinc-300 hover:bg-zinc-50 dark:bg-white/10 dark:ring-white/10">
                                <flux:icon.key class="size-3.5" /> Contraseña
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
