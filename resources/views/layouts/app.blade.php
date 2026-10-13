<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="h-full bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div x-data="{ sidebar: false }" x-on:keydown.escape.window="sidebar = false" class="min-h-full">
            {{-- Fondo del drawer en móvil --}}
            <div x-show="sidebar" x-transition.opacity x-on:click="sidebar = false"
                class="fixed inset-0 z-40 bg-zinc-900/40 backdrop-blur-sm lg:hidden" x-cloak></div>

            <livewire:layout.navigation />

            <div class="lg:pl-64">
                {{-- Barra superior móvil --}}
                <div class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-zinc-200 bg-white/80 px-4 backdrop-blur lg:hidden dark:border-white/10 dark:bg-zinc-900/80">
                    <button type="button" x-on:click="sidebar = true" class="-m-2 p-2 text-zinc-500" aria-label="Abrir menú">
                        <flux:icon.bars-3 class="size-6" />
                    </button>
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 font-semibold">
                        <x-application-logo class="size-7 text-indigo-600" />
                        {{ config('app.name') }}
                    </a>
                </div>

                <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
                    @isset($header)
                        <div class="mb-8">{{ $header }}</div>
                    @endisset

                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-toasts />
        @fluxScripts
    </body>
</html>
