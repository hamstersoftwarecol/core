<div x-data="{ toasts: [] }" x-on:toast.window="toasts.push({ id: Date.now(), ...$event.detail })">
    <div class="mb-4 flex items-center justify-between">
        <span class="text-sm text-gray-500">Instaladas en <code>apps/</code></span>
    </div>

    @if ($flash)
        <div @class([
            'mb-4 rounded px-4 py-3 text-sm',
            'bg-green-100 text-green-800' => $flashType === 'success',
            'bg-red-100 text-red-800' => $flashType === 'error',
        ])>{{ $flash }}</div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium">App</th>
                    <th class="px-4 py-2 text-left font-medium">Versión</th>
                    <th class="px-4 py-2 text-left font-medium">Estado</th>
                    <th class="px-4 py-2 text-left font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($apps as $app)
                    <tr>
                        <td class="px-4 py-2">
                            <div class="font-medium">{{ $app['name'] }}</div>
                            <div class="text-xs text-gray-500">{{ $app['slug'] }}</div>
                        </td>
                        <td class="px-4 py-2">
                            {{ $app['version'] }}
                            @if ($app['installed_version'] && $app['installed_version'] !== $app['version'])
                                <div class="text-xs text-amber-600">instalada {{ $app['installed_version'] }} — ejecuta <code>apps:verify</code></div>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-100 text-green-800' => $app['status'] === 'active',
                                'bg-gray-100 text-gray-700' => $app['status'] === 'inactive',
                                'bg-red-100 text-red-800' => $app['status'] === 'error',
                            ])>{{ $app['status'] }}</span>
                            @if ($app['last_error'])
                                <div class="mt-1 max-w-md text-xs text-red-600">{{ $app['last_error'] }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex flex-wrap gap-2">
                                @if ($app['status'] !== 'active')
                                    <button wire:click="activate('{{ $app['slug'] }}')"
                                        class="rounded bg-blue-600 px-3 py-1 text-xs text-white hover:bg-blue-700">
                                        {{ $app['status'] === 'error' ? 'Reintentar' : 'Activar' }}
                                    </button>
                                @else
                                    <button wire:click="deactivate('{{ $app['slug'] }}')"
                                        class="rounded bg-gray-200 px-3 py-1 text-xs hover:bg-gray-300">
                                        Desactivar
                                    </button>
                                @endif

                                <a href="{{ route('admin.apps.config', $app['slug']) }}" wire:navigate
                                    class="rounded bg-gray-100 px-3 py-1 text-xs hover:bg-gray-200">
                                    Configurar
                                </a>

                                <button wire:click="confirmUninstall('{{ $app['slug'] }}')"
                                    class="rounded bg-red-100 px-3 py-1 text-xs text-red-700 hover:bg-red-200">
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
                                            class="rounded bg-gray-200 px-2 py-1">Cancelar</button>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No hay apps en <code>apps/</code></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
