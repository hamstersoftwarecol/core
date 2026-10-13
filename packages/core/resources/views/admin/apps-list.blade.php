<div>
    <div class="mb-4 flex items-center justify-between">
        <span class="text-sm text-zinc-500">Instaladas en <code>apps/</code></span>
    </div>


    <div class="overflow-x-auto rounded-2xl bg-white shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-white/10 text-sm">
            <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">App</th>
                    <th class="px-4 py-3 text-left font-medium">Versión</th>
                    <th class="px-4 py-3 text-left font-medium">Estado</th>
                    <th class="px-4 py-3 text-left font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                @forelse ($apps as $app)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $app['name'] }}</div>
                            <div class="text-xs text-zinc-500">{{ $app['slug'] }}</div>
                        </td>
                        <td class="px-4 py-3">
                            {{ $app['version'] }}
                            @if ($app['installed_version'] && $app['installed_version'] !== $app['version'])
                                <div class="text-xs text-amber-600">instalada {{ $app['installed_version'] }} — ejecuta <code>apps:verify</code></div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-100 text-green-800' => $app['status'] === 'active',
                                'bg-zinc-100 text-zinc-700' => $app['status'] === 'inactive',
                                'bg-red-100 text-red-800' => $app['status'] === 'error',
                            ])>{{ $app['status'] }}</span>
                            @if ($app['last_error'])
                                <div class="mt-1 max-w-md text-xs text-red-600">{{ $app['last_error'] }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                @if ($app['status'] !== 'active')
                                    <button wire:click="activate('{{ $app['slug'] }}')"
                                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                                        {{ $app['status'] === 'error' ? 'Reintentar' : 'Activar' }}
                                    </button>
                                @else
                                    <button wire:click="deactivate('{{ $app['slug'] }}')"
                                        class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold ring-1 ring-zinc-300 hover:bg-zinc-50 dark:bg-white/10 dark:ring-white/10">
                                        Desactivar
                                    </button>
                                @endif

                                <a href="{{ route('admin.apps.config', $app['slug']) }}" wire:navigate
                                    class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold ring-1 ring-zinc-300 hover:bg-zinc-50 dark:bg-white/10 dark:ring-white/10">
                                    Configurar
                                </a>

                                <button wire:click="confirmUninstall('{{ $app['slug'] }}')"
                                    class="rounded-lg px-3 py-1.5 text-xs font-semibold text-red-600 ring-1 ring-red-200 hover:bg-red-50 dark:ring-red-500/30 dark:hover:bg-red-500/10">
                                    Desinstalar
                                </button>
                            </div>

                            @if ($confirming === 'uninstall' && $confirmSlug === $app['slug'])
                                <div class="mt-2 rounded border border-red-300 bg-red-50 p-2 text-xs">
                                    Escribe <strong>{{ $app['slug'] }}</strong> para confirmar la desinstalación
                                    (se eliminarán sus tablas y datos):
                                    <input type="text" class="mt-1 w-full rounded border px-2 py-1"
                                        wire:model.live="confirmSlug">
                                    <div class="mt-2 flex gap-2">
                                        <button wire:click="uninstall('{{ $app['slug'] }}')"
                                            class="rounded bg-red-600 px-2 py-1 text-white">Sí, desinstalar</button>
                                        <button wire:click="cancelUninstall"
                                            class="rounded bg-zinc-200 px-2 py-1">Cancelar</button>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-500">No hay apps en <code>apps/</code></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
