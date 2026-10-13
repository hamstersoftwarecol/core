<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Reportes" description="Registros por tipo de contenido de las apps activas." />
    </x-slot>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-white/10 text-sm">
                <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Tipo</th>
                        <th class="px-4 py-3 text-left font-medium">App</th>
                        <th class="px-4 py-3 text-right font-medium">Registros</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['label'] }} <span class="text-xs text-zinc-500">{{ $row['slug'] }}</span></td>
                            <td class="px-4 py-3">{{ $row['app'] }}</td>
                            <td class="px-4 py-3 text-right">{{ $row['count'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-zinc-500">Ninguna app activa publica tipos de contenido.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
</x-app-layout>
