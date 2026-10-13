<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reportes</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Tipo</th>
                            <th class="px-4 py-2 text-left font-medium">App</th>
                            <th class="px-4 py-2 text-right font-medium">Registros</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-4 py-2">{{ $row['label'] }} <span class="text-xs text-gray-500">{{ $row['slug'] }}</span></td>
                                <td class="px-4 py-2">{{ $row['app'] }}</td>
                                <td class="px-4 py-2 text-right">{{ $row['count'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">Ninguna app activa publica tipos de contenido.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
