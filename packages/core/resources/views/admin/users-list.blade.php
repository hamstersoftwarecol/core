<div class="space-y-4">
    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o email"
        class="w-full max-w-sm rounded-lg border-zinc-300 dark:border-white/10 dark:bg-white/5 text-sm">

    <div class="overflow-x-auto rounded-2xl bg-white shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-white/10 text-sm">
            <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Usuario</th>
                    <th class="px-4 py-3 text-left font-medium">Roles</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-4 py-3">
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
