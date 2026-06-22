<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('Se enviará un enlace de recuperación si la cuenta existe.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header title="¿Olvidaste tu contraseña?" description="Ingresa tu correo para recibir un enlace de recuperación" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
        <!-- Email Address -->
        <div class="grid gap-2">
            <flux:input wire:model="email" label="{{ __('Correo electrónico') }}" type="email" name="email" required autofocus placeholder="email@example.com" />
        </div>

        <flux:button variant="primary" type="submit" class="w-full">{{ __('Enviar enlace de recuperación') }}</flux:button>
    </form>

    <div class="space-x-1 text-center text-sm text-zinc-400">
        O, volver a
        <x-text-link href="{{ route('login') }}">iniciar sesión</x-text-link>
    </div>
</div>
