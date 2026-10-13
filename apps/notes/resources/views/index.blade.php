<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Notas" description="Las notas más recientes." />
    </x-slot>

        <ul class="divide-y divide-zinc-100 rounded-2xl bg-white shadow-xs ring-1 ring-zinc-200 dark:divide-white/5 dark:bg-zinc-900 dark:ring-white/10">
            @forelse ($notes as $note)
                <li class="p-4"><strong>{{ $note->title }}</strong></li>
            @empty
                <li class="p-4 text-zinc-500">Aún no hay notas.</li>
            @endforelse
        </ul>
</x-app-layout>
