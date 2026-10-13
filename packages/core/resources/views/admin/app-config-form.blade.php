<div class="rounded-2xl bg-white shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10 p-6">

    @if ($schema === [])
        <p class="text-sm text-zinc-500">Esta app no declara opciones en <code>config</code> de su app.json.</p>
    @else
        <form wire:submit="save" class="space-y-4">
            @foreach ($schema as $key => $field)
                <div>
                    <label class="block text-sm font-medium text-zinc-700" for="cfg-{{ $key }}">
                        {{ $field['label'] ?? $key }}
                    </label>
                    @if ($field['type'] === 'boolean')
                        <input id="cfg-{{ $key }}" type="checkbox" wire:model="values.{{ $key }}" class="mt-1 rounded-lg border-zinc-300 dark:border-white/10 dark:bg-white/5">
                    @else
                        <input id="cfg-{{ $key }}" type="{{ $field['type'] === 'number' ? 'number' : 'text' }}"
                            wire:model="values.{{ $key }}" class="mt-1 w-full rounded-lg border-zinc-300 dark:border-white/10 dark:bg-white/5">
                    @endif
                </div>
            @endforeach

            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-3 text-sm text-white hover:bg-indigo-500">Guardar</button>
                <a href="{{ route('admin.apps.index') }}" wire:navigate class="rounded bg-zinc-200 px-4 py-3 text-sm">Volver</a>
            </div>
        </form>
    @endif
</div>
