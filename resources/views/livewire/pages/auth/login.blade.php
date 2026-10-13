<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <x-auth-header title="Bienvenido de nuevo" description="Inicia sesión para continuar en tu panel." />

    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="mt-2" type="email" name="email" required autofocus autocomplete="username" placeholder="tu@empresa.com" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">¿La olvidaste?</a>
                @endif
            </div>
            <x-text-input wire:model="form.password" id="password" class="mt-2" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <label for="remember" class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember" class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-600 dark:border-white/20 dark:bg-white/5">
            {{ __('Remember me') }}
        </label>

        <x-primary-button class="w-full" wire:loading.attr="disabled">
            {{ __('Log in') }}
        </x-primary-button>
    </form>

    @if (Route::has('register'))
        <p class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
            ¿No tienes cuenta? <a href="{{ route('register') }}" wire:navigate class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Crear una cuenta</a>
        </p>
    @endif
</div>
