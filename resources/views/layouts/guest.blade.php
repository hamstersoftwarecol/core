<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="h-full bg-white font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div class="flex min-h-full">
            {{-- Formulario --}}
            <div class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-12 lg:flex-none lg:px-20 xl:px-24">
                <div class="mx-auto w-full max-w-sm">
                    <a href="/" wire:navigate class="mb-10 inline-flex items-center gap-2 text-lg font-semibold">
                        <x-application-logo class="size-9 text-indigo-600" />
                        {{ config('app.name') }}
                    </a>

                    {{ $slot }}
                </div>
            </div>

            {{-- Panel de marca --}}
            <div class="relative hidden flex-1 overflow-hidden bg-zinc-950 lg:block">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(99,102,241,.45),transparent_55%),radial-gradient(ellipse_at_bottom_left,rgba(168,85,247,.3),transparent_50%)]"></div>
                <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,.04)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,.04)_1px,transparent_1px)] bg-[size:48px_48px]"></div>

                <div class="relative flex h-full flex-col justify-end p-16 text-white">
                    <div class="mb-10 grid max-w-md grid-cols-3 gap-3">
                        @foreach (['squares-2x2' => 'Apps', 'shield-check' => 'Roles', 'chart-bar' => 'Reportes'] as $icon => $label)
                            <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur">
                                <flux:icon :name="$icon" class="size-5 text-indigo-300" />
                                <div class="mt-3 text-sm font-medium">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                    <blockquote class="max-w-md text-2xl font-medium leading-snug tracking-tight">
                        Activa solo lo que tu negocio necesita. Cada módulo es una app que enciendes y apagas en un clic.
                    </blockquote>
                    <p class="mt-4 text-sm text-zinc-400">{{ config('app.name') }} · núcleo modular sobre Laravel</p>
                </div>
            </div>
        </div>

        <x-toasts />
    </body>
</html>
