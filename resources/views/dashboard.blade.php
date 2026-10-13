<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="'Hola, '.str(auth()->user()->name)->before(' ')" description="Este es el resumen de tus apps activas." />
    </x-slot>

    <x-core-dashboard-widgets />

    @role('super-admin')
        <div class="mt-8 rounded-2xl border border-dashed border-zinc-300 p-6 dark:border-white/15">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-indigo-600/10 text-indigo-600 dark:text-indigo-400">
                        <flux:icon.puzzle-piece class="size-5" />
                    </div>
                    <div>
                        <div class="font-medium">Gestiona tus apps</div>
                        <div class="text-sm text-zinc-500">Activa módulos nuevos o configura los existentes.</div>
                    </div>
                </div>
                <a href="{{ route('admin.apps.index') }}" wire:navigate class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">Ir a Apps</a>
            </div>
        </div>
    @endrole
</x-app-layout>
