<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <x-auth-header title="Verifica tu email" description="Te enviamos un enlace de verificación. Ábrelo para activar tu cuenta; si no llegó, te enviamos otro." />

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status class="mb-6" :status="__('A new verification link has been sent to the email address you provided during registration.')" />
    @endif

    <div class="space-y-4">
        <x-primary-button class="w-full" type="button" wire:click="sendVerification" wire:loading.attr="disabled">
            {{ __('Resend Verification Email') }}
        </x-primary-button>

        <x-secondary-button class="w-full" wire:click="logout">
            {{ __('Log Out') }}
        </x-secondary-button>
    </div>
</div>
