<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        try {
            $validated['password'] = Hash::make($validated['password']);

            event(new Registered(($user = User::create($validated))));

            Auth::login($user);

            $this->redirect(route('dashboard', absolute: false), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo crear la cuenta. Intenta de nuevo.');
        }
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Crear una cuenta" description="Ingresa tus datos para crear tu cuenta" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        <!-- Name -->
        <div class="grid gap-2">
            <flux:input wire:model="name" id="name" label="{{ __('Nombre') }}" type="text" name="name" required autofocus autocomplete="name" placeholder="Nombre completo" />
            @error('name') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
        </div>

        <!-- Email Address -->
        <div class="grid gap-2">
            <flux:input wire:model="email" id="email" label="{{ __('Correo electrónico') }}" type="email" name="email" required autocomplete="email" placeholder="email@example.com" />
            @error('email') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
        </div>

        <!-- Password -->
        <div class="grid gap-2">
            <flux:input
                wire:model="password"
                id="password"
                label="{{ __('Contraseña') }}"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Contraseña"
            />
            @error('password') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
        </div>

        <!-- Confirm Password -->
        <div class="grid gap-2">
            <flux:input
                wire:model="password_confirmation"
                id="password_confirmation"
                label="{{ __('Confirmar contraseña') }}"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirmar contraseña"
            />
        </div>

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Crear cuenta') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
        ¿Ya tienes una cuenta?
        <x-text-link href="{{ route('login') }}">Iniciar sesión</x-text-link>
    </div>
</div>
