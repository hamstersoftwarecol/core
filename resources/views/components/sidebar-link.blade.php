@props(['active' => false, 'icon' => null])

<a {{ $attributes->class([
    'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
    'bg-zinc-900/5 text-zinc-900 dark:bg-white/10 dark:text-white' => $active,
    'text-zinc-600 hover:bg-zinc-900/5 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/5 dark:hover:text-white' => ! $active,
]) }}>
    @if ($icon)
        <flux:icon :name="$icon" variant="outline" @class([
            'size-5 shrink-0',
            'text-indigo-600 dark:text-indigo-400' => $active,
            'text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300' => ! $active,
        ]) />
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
