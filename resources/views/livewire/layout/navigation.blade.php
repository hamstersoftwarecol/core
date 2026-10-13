<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<aside x-bind:class="sidebar ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-zinc-200 bg-white transition-transform duration-200 lg:translate-x-0 dark:border-white/10 dark:bg-zinc-900">
    <div class="flex h-16 shrink-0 items-center justify-between px-5">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 font-semibold">
            <x-application-logo class="size-8 text-indigo-600" />
            <span>{{ config('app.name') }}</span>
        </a>
        <button type="button" x-on:click="sidebar = false" class="text-zinc-400 lg:hidden" aria-label="Cerrar menú">
            <flux:icon.x-mark class="size-5" />
        </button>
    </div>

    <nav class="flex flex-1 flex-col gap-6 overflow-y-auto px-3 py-4">
        <div class="space-y-1">
            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home" wire:navigate>
                {{ __('Dashboard') }}
            </x-sidebar-link>
        </div>

        <x-core-app-menu />

        @role('super-admin')
            <div>
                <div class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-zinc-400">Administración</div>
                <div class="space-y-1">
                    <x-sidebar-link :href="route('admin.apps.index')" :active="request()->routeIs('admin.apps.*')" icon="squares-2x2" wire:navigate>
                        {{ __('Apps') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" icon="users" wire:navigate>
                        {{ __('Usuarios') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')" icon="shield-check" wire:navigate>
                        {{ __('Roles') }}
                    </x-sidebar-link>
                </div>
            </div>
        @endrole
    </nav>

    <div class="border-t border-zinc-200 p-3 dark:border-white/10">
        <flux:dropdown position="top" align="start">
            <button type="button" class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left hover:bg-zinc-900/5 dark:hover:bg-white/5">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white">
                    {{ str(auth()->user()->name)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                    <span class="block truncate text-xs text-zinc-500">{{ auth()->user()->email }}</span>
                </span>
                <flux:icon.chevron-up-down class="size-4 text-zinc-400" />
            </button>

            <flux:menu>
                <flux:menu.item :href="route('profile')" icon="user-circle" wire:navigate>{{ __('Profile') }}</flux:menu.item>
                <flux:menu.separator />
                <flux:menu.item wire:click="logout" icon="arrow-right-start-on-rectangle">{{ __('Log Out') }}</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>
</aside>
