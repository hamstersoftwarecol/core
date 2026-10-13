<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Perfil" description="Tu información, contraseña y cuenta." />
    </x-slot>

    <div class="space-y-6">
        @foreach (['profile.update-profile-information-form', 'profile.update-password-form', 'profile.delete-user-form'] as $form)
            <div class="rounded-2xl bg-white p-6 shadow-xs ring-1 ring-zinc-200 sm:p-8 dark:bg-zinc-900 dark:ring-white/10">
                <div class="max-w-xl">
                    @livewire($form)
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
