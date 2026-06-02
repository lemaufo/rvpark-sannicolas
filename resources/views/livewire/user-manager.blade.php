<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

new class extends Component {
    public $users = [];

    public $name = '';
    public $email = '';
    public $password = '';
    public $role = 'receptionist';

    public $editingUserId = null;
    public $modalTitle = 'Nuevo Usuario';

    public function mount()
    {
        $this->loadUsers();
    }

    public function loadUsers()
    {
        $this->users = User::orderBy('name')->get()->toArray();
    }

    // Abre modal para CREAR
    public function openCreateModal()
    {
        $this->reset(['name', 'email', 'password', 'editingUserId']);
        $this->resetValidation();
        $this->role = 'receptionist';
        $this->modalTitle = 'Nuevo Usuario';

        \Flux::modal('create-user')->show();
    }

    // Abre modal para EDITAR
    public function openEditModal($userId)
    {
        $this->resetValidation();
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = ''; // No mostrar contraseña
        $this->role = $user->role;
        $this->modalTitle = 'Editar Usuario';

        \Flux::modal('create-user')->show();
    }

    // Guardar (crear o actualizar)
    public function saveUser()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->editingUserId,
            'role' => 'required|in:admin,receptionist',
        ];

        if (!$this->editingUserId) {
            $rules['password'] = 'required|min:8';
        } else {
            $rules['password'] = 'nullable|min:8';
        }

        $messages = [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'role.required' => 'Debes seleccionar un rol.',
        ];

        $this->validate($rules, $messages);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            User::find($this->editingUserId)->update($data);
            session()->flash('message', 'Usuario actualizado correctamente.');
        } else {
            User::create($data);
            session()->flash('message', 'Usuario creado correctamente.');
        }

        $this->loadUsers();
        $this->reset(['name', 'email', 'password', 'editingUserId']);
        $this->resetValidation();

        \Flux::modal('create-user')->close();
    }
    // Reset de contraseña (genera una nueva aleatoria)
    public function resetUserPassword($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $newPassword = Str::random(12);
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Mostrar la nueva contraseña en un flash message
        session()->flash('password-reset', "Nueva contraseña para {$user->name}: <strong>{$newPassword}</strong>");

        $this->loadUsers();
    }
}; ?>

<div class="mt-8">

    {{-- Mensajes flash --}}
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show"
            class="mb-4 p-4 bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-2xl border border-emerald-200 dark:border-emerald-800">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('password-reset'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show"
            class="mb-4 p-4 bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 rounded-2xl border border-orange-200 dark:border-orange-800">
            {!! session('password-reset') !!}
        </div>
    @endif

    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">

        <div class="p-6 flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800">
            <div>
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white">
                    Usuarios Registrados
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Administración de cuentas del sistema
                </p>
            </div>

            <button wire:click="openCreateModal"
                class="px-4 py-2 bg-[#4a5d41] text-white rounded-xl font-semibold hover:bg-[#3d4d36] transition">
                Nuevo Usuario
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                        <th class="px-6 py-4 text-left text-sm font-bold text-zinc-600 dark:text-zinc-300">Nombre</th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-zinc-600 dark:text-zinc-300">Correo</th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-zinc-600 dark:text-zinc-300">Rol</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-zinc-600 dark:text-zinc-300">Acciones
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-t border-zinc-200 dark:border-zinc-800">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-zinc-900 dark:text-white">
                                    {{ $user['name'] }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-300">
                                {{ $user['email'] }}
                            </td>
                            <td class="px-6 py-4">
                                @if ($user['role'] === 'admin')
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                        Administrador
                                    </span>
                                @else
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-bold bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        Recepcionista
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex gap-2 justify-center">
                                    {{-- Botón Editar --}}
                                    <button wire:click="openEditModal({{ $user['id'] }})"
                                        class="px-3 py-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition">
                                        Editar
                                    </button>

                                    {{-- Botón Reset Contraseña --}}
                                    <button wire:click="resetUserPassword({{ $user['id'] }})"
                                        wire:confirm="¿Estás seguro de restablecer la contraseña de {{ $user['name'] }}?"
                                        class="px-3 py-1.5 border border-[#4a5d41]/20 text-[#4a5d41] rounded-xl text-sm font-medium hover:bg-[#4a5d41] hover:text-white transition">
                                        Restablecer Contraseña
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-zinc-500 dark:text-zinc-400">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Crear/Editar --}}
    <flux:modal name="create-user" class="md:w-full md:max-w-md">
        <div class="p-6">
            <h2 class="text-2xl font-bold mb-6 text-zinc-900 dark:text-white">
                {{ $modalTitle }}
            </h2>

            <div class="space-y-4">
                <flux:input wire:model="name" label="Nombre" placeholder="Nombre completo" />

                <flux:input wire:model="email" type="email" label="Correo" placeholder="correo@ejemplo.com" />

                <flux:input wire:model="password" type="password"
                    label="{{ $editingUserId ? 'Nueva Contraseña (dejar vacío para no cambiar)' : 'Contraseña' }}" />

                <div>
                    <label class="block mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Rol
                    </label>
                    <select wire:model="role"
                        class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3">
                        <option value="receptionist">Recepcionista</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:modal.close>
                        <button type="button" class="px-4 py-2 bg-zinc-200 dark:bg-zinc-700 rounded-xl">
                            Cancelar
                        </button>
                    </flux:modal.close>

                    <button wire:click="saveUser" type="button" class="px-4 py-2 bg-[#4a5d41] text-white rounded-xl">
                        {{ $editingUserId ? 'Actualizar' : 'Guardar Usuario' }}
                    </button>
                </div>
            </div>
        </div>
    </flux:modal>

</div>
