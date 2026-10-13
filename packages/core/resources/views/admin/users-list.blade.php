<div class="space-y-4">
    @if ($flash)
        <div class="rounded bg-green-100 px-4 py-3 text-sm text-green-800">{{ $flash }}</div>
    @endif

    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o email"
        class="w-full max-w-sm rounded border-gray-300 text-sm">

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium">Usuario</th>
                    <th class="px-4 py-2 text-left font-medium">Roles</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-2">
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex flex-wrap gap-3">
                                @foreach ($roles as $role)
                                    <label class="flex items-center gap-1 text-xs">
                                        <input type="checkbox" wire:click="toggleRole({{ $user->id }}, '{{ $role }}')"
                                            @checked($user->roles->contains('name', $role))>
                                        {{ $role }}
                                    </label>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
