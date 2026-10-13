@foreach ($items as $item)
    @if ($responsive)
        <x-responsive-nav-link :href="route($item->route)" :active="request()->routeIs('apps.'.$item->app.'.*')" wire:navigate>
            {{ $item->label }}
        </x-responsive-nav-link>
    @else
        <x-nav-link :href="route($item->route)" :active="request()->routeIs('apps.'.$item->app.'.*')" wire:navigate>
            {{ $item->label }}
        </x-nav-link>
    @endif
@endforeach
