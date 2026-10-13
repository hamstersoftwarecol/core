<div class="space-y-4">
    @if ($flash)
        <div class="rounded bg-green-100 px-4 py-3 text-sm text-green-800">{{ $flash }}</div>
    @endif

    <form wire:submit="create" class="flex gap-2">
        <input type="text" wire:model="newRole" placeholder="nuevo-rol" class="rounded border-gray-300 text-sm">
        <button class="rounded bg-blue-600 px-3 py-1 text-sm text-white">Crear rol</button>
        @error('newRole') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium">Rol</th>
                    @foreach ($permissions as $permission)
                        <th class="px-2 py-2 text-left text-xs font-medium">{{ $permission->name }}</th>
                    @endforeach
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($roles as $role)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $role->name }}</td>
                        @foreach ($permissions as $permission)
                            <td class="px-2 py-2">
                                @if ($role->name === 'super-admin')
                                    <span class="text-xs text-gray-400">todos</span>
                                @else
                                    <input type="checkbox" wire:click="toggle({{ $role->id }}, {{ $permission->id }})"
                                        @checked($role->permissions->contains($permission))>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-2 text-right">
                            @unless ($role->name === 'super-admin')
                                <button wire:click="delete({{ $role->id }})" wire:confirm="¿Eliminar el rol {{ $role->name }}?"
                                    class="rounded bg-red-100 px-2 py-1 text-xs text-red-700">Eliminar</button>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
