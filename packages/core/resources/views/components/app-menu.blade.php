@if ($items)
    <div>
        <div class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-zinc-400">Apps</div>
        <div class="space-y-1">
            @foreach ($items as $item)
                <x-sidebar-link :href="route($item->route)" :active="request()->routeIs('apps.'.$item->app.'.*')" :icon="$item->icon ?? 'puzzle-piece'" wire:navigate>
                    {{ $item->label }}
                </x-sidebar-link>
            @endforeach
        </div>
    </div>
@endif
