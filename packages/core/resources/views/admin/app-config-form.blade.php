<div class="rounded-lg border border-gray-200 bg-white p-6">
    @if ($flash)
        <div @class([
            'mb-4 rounded px-4 py-3 text-sm',
            'bg-green-100 text-green-800' => $flashType === 'success',
            'bg-red-100 text-red-800' => $flashType === 'error',
        ])>{{ $flash }}</div>
    @endif

    @if ($schema === [])
        <p class="text-sm text-gray-500">Esta app no declara opciones en <code>config</code> de su app.json.</p>
    @else
        <form wire:submit="save" class="space-y-4">
            @foreach ($schema as $key => $field)
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="cfg-{{ $key }}">
                        {{ $field['label'] ?? $key }}
                    </label>
                    @if ($field['type'] === 'boolean')
                        <input id="cfg-{{ $key }}" type="checkbox" wire:model="values.{{ $key }}" class="mt-1 rounded border-gray-300">
                    @else
                        <input id="cfg-{{ $key }}" type="{{ $field['type'] === 'number' ? 'number' : 'text' }}"
                            wire:model="values.{{ $key }}" class="mt-1 w-full rounded border-gray-300">
                    @endif
                </div>
            @endforeach

            <div class="flex gap-2">
                <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Guardar</button>
                <a href="{{ route('admin.apps.index') }}" wire:navigate class="rounded bg-gray-200 px-4 py-2 text-sm">Volver</a>
            </div>
        </form>
    @endif
</div>
