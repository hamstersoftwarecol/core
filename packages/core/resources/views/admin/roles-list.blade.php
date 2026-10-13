<div class="space-y-4">
    <form wire:submit="create" class="flex gap-2">
        <input type="text" wire:model="newRole" placeholder="nuevo-rol" class="rounded-lg border-zinc-300 dark:border-white/10 dark:bg-white/5 text-sm">
        <button class="rounded-lg bg-indigo-600 px-3 py-1 text-sm text-white">Crear rol</button>
        @error('newRole') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-white/10 text-sm">
            <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Rol</th>
                    @foreach ($permissions as $permission)
                        <th class="px-2 py-2 text-left text-xs font-medium">{{ $permission->name }}</th>
                    @endforeach
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                @foreach ($roles as $role)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $role->name }}</td>
                        @foreach ($permissions as $permission)
                            <td class="px-2 py-2">
                                @if ($role->name === 'super-admin')
                                    <span class="text-xs text-zinc-400">todos</span>
                                @else
                                    <input type="checkbox" wire:click="toggle({{ $role->id }}, {{ $permission->id }})"
                                        @checked($role->permissions->contains($permission))>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-right">
                            @unless ($role->name === 'super-admin')
                                <button wire:click="delete({{ $role->id }})" wire:confirm="¿Eliminar el rol {{ $role->name }}?"
                                    class="rounded-lg px-2 py-1 text-xs font-semibold text-red-600 ring-1 ring-red-200 hover:bg-red-50">Eliminar</button>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
