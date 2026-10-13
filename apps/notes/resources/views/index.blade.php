<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notas</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <ul class="divide-y rounded-lg bg-white shadow-sm">
                @forelse ($notes as $note)
                    <li class="p-4"><strong>{{ $note->title }}</strong></li>
                @empty
                    <li class="p-4 text-gray-500">Aún no hay notas.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
