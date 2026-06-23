<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser un texto.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.max' => 'El correo no debe exceder 255 caracteres.',
            'email.unique' => 'Este correo ya está registrado por otro usuario.',
        ]);

        try {
            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $this->dispatch('swal-success', ['title' => 'Perfil actualizado', 'message' => 'Tu información de perfil se ha guardado correctamente.']);
            $this->dispatch('profile-updated', name: $user->name);
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo actualizar el perfil. Intenta de nuevo.');
        }
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        try {
            $user = Auth::user();

            if ($user->hasVerifiedEmail()) {
                $this->redirectIntended(default: route('dashboard', absolute: false));

                return;
            }

            $user->sendEmailVerificationNotification();

            Session::flash('status', 'verification-link-sent');
            $this->dispatch('swal-success', ['title' => 'Correo enviado', 'message' => 'Se ha enviado un nuevo enlace de verificación a tu correo electrónico.']);
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo enviar el correo de verificación. Intenta de nuevo.');
        }
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout heading="Perfil" subheading="Actualiza tu nombre y correo electrónico">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" label="{{ __('Nombre') }}" type="text" name="name" required autofocus autocomplete="name" />
            @error('name') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror

            <div>
                <flux:input wire:model="email" label="{{ __('Correo electrónico') }}" type="email" name="email" required autocomplete="email" />
                @error('email') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <p class="mt-2 text-sm text-gray-800">
                            {{ __('Tu correo electrónico no está verificado.') }}

                            <button
                                wire:click.prevent="resendVerificationNotification"
                                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                {{ __('Haz clic aquí para reenviar el correo de verificación.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-green-600">
                                {{ __('Se ha enviado un nuevo enlace de verificación a tu correo electrónico.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Guardar') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Guardado.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
