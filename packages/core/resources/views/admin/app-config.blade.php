<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="'Configurar '.$slug" description="Opciones declaradas en el app.json de la app." />
    </x-slot>

    <div class="max-w-2xl">
        <livewire:admin.app-config :slug="$slug" />
    </div>
</x-app-layout>
