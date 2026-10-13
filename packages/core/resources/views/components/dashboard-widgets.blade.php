@if ($rendered)
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($rendered as $html)
            <div class="rounded-2xl bg-white p-6 shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10">{!! $html !!}</div>
        @endforeach
    </div>
@else
    <div class="rounded-2xl bg-white p-10 text-center shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10">
        <flux:icon.squares-plus class="mx-auto size-8 text-zinc-300" />
        <p class="mt-3 text-sm text-zinc-500">Aún no hay widgets. Aparecerán aquí cuando actives apps que los incluyan.</p>
    </div>
@endif
