<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('layouts.partials.head', ['title' => 'Tu plataforma, módulo a módulo'])
        <meta name="description" content="{{ config('app.name') }}: un núcleo Laravel donde cada módulo es una app que activas, configuras y desactivas desde un panel.">
    </head>
    <body class="bg-white font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        {{-- Navegación --}}
        <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-zinc-200/70 bg-white/80 backdrop-blur dark:border-white/10 dark:bg-zinc-950/80">
            <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-2 font-semibold">
                    <x-application-logo class="size-8 text-indigo-600" />
                    {{ config('app.name') }}
                </a>

                <div class="hidden items-center gap-8 text-sm font-medium text-zinc-600 md:flex dark:text-zinc-400">
                    <a href="#funciones" class="hover:text-zinc-900 dark:hover:text-white">Funciones</a>
                    <a href="#como-funciona" class="hover:text-zinc-900 dark:hover:text-white">Cómo funciona</a>
                    <a href="#desarrolladores" class="hover:text-zinc-900 dark:hover:text-white">Desarrolladores</a>
                </div>

                <div class="hidden items-center gap-3 md:flex">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">Ir al panel</a>
                    @else
                        <a href="{{ route('login') }}" class="px-3 py-2 text-sm font-semibold text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">Iniciar sesión</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">Empezar gratis</a>
                        @endif
                    @endauth
                </div>

                <button type="button" x-on:click="open = ! open" class="-m-2 p-2 text-zinc-600 md:hidden" aria-label="Menú">
                    <flux:icon.bars-3 class="size-6" x-show="! open" />
                    <flux:icon.x-mark class="size-6" x-show="open" x-cloak />
                </button>
            </nav>

            <div x-show="open" x-cloak x-transition class="border-t border-zinc-200 px-4 py-4 md:hidden dark:border-white/10">
                <div class="flex flex-col gap-3 text-sm font-medium">
                    <a href="#funciones" x-on:click="open = false">Funciones</a>
                    <a href="#como-funciona" x-on:click="open = false">Cómo funciona</a>
                    <a href="#desarrolladores" x-on:click="open = false">Desarrolladores</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="mt-2 rounded-lg bg-zinc-900 px-4 py-2 text-center text-white">Ir al panel</a>
                    @else
                        <a href="{{ route('login') }}" class="mt-2 rounded-lg px-4 py-2 text-center ring-1 ring-zinc-300">Iniciar sesión</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-center text-white">Empezar gratis</a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="relative isolate overflow-hidden">
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(60%_50%_at_50%_0%,rgba(99,102,241,.18),transparent)] dark:bg-[radial-gradient(60%_50%_at_50%_0%,rgba(99,102,241,.3),transparent)]"></div>
                <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgba(24,24,27,.05)_1px,transparent_1px),linear-gradient(to_bottom,rgba(24,24,27,.05)_1px,transparent_1px)] bg-[size:56px_56px] [mask-image:radial-gradient(ellipse_at_top,black,transparent_70%)] dark:bg-[linear-gradient(to_right,rgba(255,255,255,.05)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,.05)_1px,transparent_1px)]"></div>

                <div class="mx-auto max-w-7xl px-4 pt-20 pb-16 text-center sm:px-6 sm:pt-28 lg:px-8">
                    <a href="#como-funciona" class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300">
                        <span class="size-1.5 rounded-full bg-indigo-500"></span>
                        Apps tipo plugin sobre Laravel
                    </a>
                    <h1 class="mx-auto mt-6 max-w-4xl text-4xl font-semibold tracking-tight text-balance sm:text-6xl">
                        Tu plataforma, <span class="bg-gradient-to-r from-indigo-600 to-violet-500 bg-clip-text text-transparent">módulo a módulo</span>
                    </h1>
                    <p class="mx-auto mt-6 max-w-2xl text-lg text-pretty text-zinc-600 dark:text-zinc-400">
                        Blog, CRM, reportes, integraciones… cada función es una app que activas, configuras y apagas desde un panel. Sin tocar el núcleo, sin perder datos.
                    </p>
                    <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Ir al panel</a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Crear cuenta</a>
                            @endif
                            <a href="{{ route('login') }}" class="rounded-lg bg-white px-5 py-3 text-sm font-semibold text-zinc-900 shadow-xs ring-1 ring-zinc-300 hover:bg-zinc-50 dark:bg-white/10 dark:text-white dark:ring-white/10">Iniciar sesión</a>
                        @endauth
                    </div>

                    {{-- Vista previa del panel --}}
                    <div class="mx-auto mt-16 max-w-5xl rounded-2xl bg-zinc-900/5 p-2 ring-1 ring-zinc-900/10 dark:bg-white/5 dark:ring-white/10">
                        <div class="overflow-hidden rounded-xl bg-white text-left shadow-2xl ring-1 ring-zinc-900/5 dark:bg-zinc-900">
                            <div class="flex items-center gap-1.5 border-b border-zinc-100 px-4 py-3 dark:border-white/5">
                                <span class="size-2.5 rounded-full bg-zinc-200 dark:bg-white/10"></span>
                                <span class="size-2.5 rounded-full bg-zinc-200 dark:bg-white/10"></span>
                                <span class="size-2.5 rounded-full bg-zinc-200 dark:bg-white/10"></span>
                                <span class="ml-3 text-xs text-zinc-400">/admin/apps</span>
                            </div>
                            <div class="grid sm:grid-cols-[200px_1fr]">
                                <div class="hidden space-y-1 border-r border-zinc-100 p-4 text-sm sm:block dark:border-white/5">
                                    @foreach (['home' => 'Dashboard', 'document-text' => 'Notas', 'chart-bar' => 'Reportes', 'squares-2x2' => 'Apps'] as $icon => $label)
                                        <div @class(['flex items-center gap-2 rounded-md px-2 py-1.5', 'bg-zinc-100 font-medium dark:bg-white/10' => $label === 'Apps', 'text-zinc-500' => $label !== 'Apps'])>
                                            <flux:icon :name="$icon" class="size-4" /> {{ $label }}
                                        </div>
                                    @endforeach
                                </div>
                                <div class="divide-y divide-zinc-100 p-2 dark:divide-white/5">
                                    @foreach ([['Notas', 'notes', 'active'], ['Reportes', 'reportes', 'active'], ['Blog', 'blog', 'inactive'], ['Facturación', 'billing', 'inactive']] as [$name, $slug, $status])
                                        <div class="flex items-center justify-between gap-4 px-3 py-3">
                                            <div>
                                                <div class="text-sm font-medium">{{ $name }}</div>
                                                <div class="text-xs text-zinc-400">{{ $slug }}</div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span @class([
                                                    'rounded-full px-2 py-0.5 text-xs font-medium',
                                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $status === 'active',
                                                    'bg-zinc-100 text-zinc-600 dark:bg-white/10 dark:text-zinc-400' => $status !== 'active',
                                                ])>{{ $status === 'active' ? 'activa' : 'inactiva' }}</span>
                                                <span @class(['relative inline-flex h-5 w-9 rounded-full transition', 'bg-indigo-600' => $status === 'active', 'bg-zinc-200 dark:bg-white/10' => $status !== 'active'])>
                                                    <span @class(['absolute top-0.5 size-4 rounded-full bg-white shadow', 'right-0.5' => $status === 'active', 'left-0.5' => $status !== 'active'])></span>
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Funciones --}}
            <section id="funciones" class="mx-auto max-w-7xl scroll-mt-20 px-4 py-24 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Todo lo que necesita un núcleo</h2>
                    <p class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Crece sin reescribir</p>
                </div>
                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['puzzle-piece', 'Apps que se activan en un clic', 'Cada módulo corre sus migraciones, crea sus permisos y aparece en el menú al activarlo.'],
                        ['shield-check', 'Roles y permisos', 'Permisos por app con spatie/laravel-permission. Quien no tiene acceso no ve ni el menú.'],
                        ['squares-plus', 'Widgets en el dashboard', 'Las apps aportan tarjetas al panel. Si una falla, se oculta y el resto sigue funcionando.'],
                        ['circle-stack', 'Datos que se conservan', 'Desactivar no borra nada. Solo desinstalar elimina las tablas, y pide confirmación.'],
                        ['arrows-right-left', 'Apps que colaboran', 'Los tipos de contenido se comparten: Reportes cuenta lo que publican las demás apps.'],
                        ['adjustments-horizontal', 'Configuración por app', 'Formularios generados desde el manifiesto de cada app, sin escribir una sola vista.'],
                    ] as [$icon, $title, $text])
                        <div class="rounded-2xl p-6 ring-1 ring-zinc-200 transition hover:shadow-lg hover:ring-zinc-300 dark:ring-white/10 dark:hover:ring-white/20">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-indigo-600/10 text-indigo-600 dark:text-indigo-400">
                                <flux:icon :name="$icon" class="size-5" />
                            </div>
                            <h3 class="mt-5 font-semibold">{{ $title }}</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Cómo funciona --}}
            <section id="como-funciona" class="scroll-mt-20 bg-zinc-50 py-24 dark:bg-white/[.02]">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Cómo funciona</h2>
                        <p class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">De la carpeta al menú en tres pasos</p>
                    </div>
                    <ol class="mt-16 grid gap-8 md:grid-cols-3">
                        @foreach ([
                            ['Añade la app', 'Copia el paquete en apps/ con su app.json. El núcleo la descubre automáticamente.'],
                            ['Actívala', 'Desde el panel o con apps:activate. Migraciones, permisos y menú quedan listos.'],
                            ['Asigna acceso', 'Da el permiso a los roles que la necesiten. El resto ni la verá.'],
                        ] as $i => [$title, $text])
                            <li class="relative rounded-2xl bg-white p-6 shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-white/10">
                                <span class="flex size-8 items-center justify-center rounded-full bg-zinc-900 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">{{ $i + 1 }}</span>
                                <h3 class="mt-5 font-semibold">{{ $title }}</h3>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $text }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>

            {{-- Desarrolladores --}}
            <section id="desarrolladores" class="mx-auto grid max-w-7xl scroll-mt-20 items-center gap-12 px-4 py-24 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div>
                    <h2 class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Para desarrolladores</h2>
                    <p class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Un contrato pequeño y claro</p>
                    <p class="mt-6 text-zinc-600 dark:text-zinc-400">
                        Tu app solo depende de <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-sm dark:bg-white/10">appcore/contracts</code>. Rutas, menú, widgets y tipos de contenido se declaran en un único método.
                    </p>
                    <ul class="mt-8 space-y-3 text-sm">
                        @foreach (['Paquetes Composer locales, versionados con tu repo', 'apps:verify en cada deploy aplica migraciones nuevas', 'Una app rota nunca tumba el sitio'] as $point)
                            <li class="flex gap-3"><flux:icon.check-circle class="size-5 text-indigo-600 dark:text-indigo-400" /> {{ $point }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="overflow-hidden rounded-2xl bg-zinc-950 shadow-2xl ring-1 ring-white/10">
                    <div class="border-b border-white/10 px-4 py-3 text-xs text-zinc-400">NotesServiceProvider.php</div>
<pre class="overflow-x-auto p-5 text-[13px] leading-6 text-zinc-300"><code><span class="text-violet-400">protected function</span> <span class="text-sky-300">bootPlugin</span>(): <span class="text-violet-400">void</span>
{
    $this-><span class="text-sky-300">routes</span>(<span class="text-emerald-300">'routes/web.php'</span>);
    $this-><span class="text-sky-300">menu</span>(<span class="text-emerald-300">'Notas'</span>, route: <span class="text-emerald-300">'index'</span>, icon: <span class="text-emerald-300">'document-text'</span>);
    $this-><span class="text-sky-300">widget</span>(NotesCountWidget::<span class="text-violet-400">class</span>);
    $this-><span class="text-sky-300">contentType</span>(<span class="text-emerald-300">'notes.note'</span>, label: <span class="text-emerald-300">'Nota'</span>);
}</code></pre>
                    <div class="border-t border-white/10 px-5 py-4 font-mono text-[13px] text-zinc-400">
                        <span class="text-indigo-400">$</span> php artisan apps:activate notes<br>
                        <span class="text-emerald-400">App "notes" activada.</span>
                    </div>
                </div>
            </section>

            {{-- CTA --}}
            <section class="px-4 pb-24 sm:px-6 lg:px-8">
                <div class="relative isolate mx-auto max-w-5xl overflow-hidden rounded-3xl bg-zinc-950 px-6 py-16 text-center shadow-2xl sm:px-16">
                    <div class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,.45),transparent_65%)]"></div>
                    <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Empieza con lo esencial. Añade el resto cuando lo necesites.</h2>
                    <div class="mt-10 flex flex-wrap justify-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-lg bg-white px-5 py-3 text-sm font-semibold text-zinc-900 hover:bg-zinc-100">Ir al panel</a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-lg bg-white px-5 py-3 text-sm font-semibold text-zinc-900 hover:bg-zinc-100">Crear cuenta</a>
                            @endif
                            <a href="{{ route('login') }}" class="rounded-lg px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/10">Iniciar sesión</a>
                        @endauth
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-zinc-200 dark:border-white/10">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-zinc-500 sm:flex-row sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <x-application-logo class="size-6 text-indigo-600" />
                    © {{ date('Y') }} {{ config('app.name') }}
                </div>
                <p>Hecho con Laravel, Livewire y Tailwind CSS.</p>
            </div>
        </footer>

        @livewireScripts
        @fluxScripts
    </body>
</html>
