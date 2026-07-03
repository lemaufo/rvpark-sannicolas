<?php

use Livewire\Volt\Component;
use App\Models\Unit;
use Carbon\Carbon;

new class extends Component {
    public $units = [];
    public $selectedUnitId = null;

    public $showReservationForm = false;
    public $guest_name = '';
    public $guest_phone = '';
    public $guest_email = '';
    public $nationality = 'Mexicana';
    public $license_plate = '';
    public $check_in = '';
    public $check_in_time = '14:00';
    public $check_out = '';
    public $check_out_time = '12:00';
    public $total_amount = 0;
    public $filtro = 'all';
    public $errorMessage = '';
    public $nationalities = [
        'Mexicana',
        'Estadounidense',
        'Canadiense',
        'Guatemalteca',
        'Salvadoreña',
        'Hondureña',
        'Colombiana',
        'Argentina',
        'Española',
        'Francesa',
        'Alemana',
        'Británica',
        'Italiana',
        'Otra'
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->units = collect(Unit::all())
            ->map(function ($unit) {
                $activeRes = \App\Models\Reservation::where('unit_id', $unit->id)->where('status', 'checked_in')->first();
                return [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'type' => ucfirst($unit->type),
                    'image' => $unit->image,
                    'status_raw' => $unit->status,
                    'status' => match ($unit->status) {
                        'available' => 'Disponible',
                        'occupied' => 'Ocupado',
                        'cleaning' => 'Limpieza',
                        default => $unit->status,
                    },
                    'color_classes' => match ($unit->status) {
                        'available' => [
                            'bg' => 'bg-emerald-500',
                            'badge_bg' => 'bg-emerald-50 dark:bg-emerald-900/30',
                            'text' => 'text-emerald-500',
                            'border' => 'border-emerald-200 dark:border-emerald-800',
                            'text_dark' => 'text-emerald-600',
                        ],
                        'occupied' => [
                            'bg' => 'bg-red-500',
                            'badge_bg' => 'bg-red-50 dark:bg-red-900/30',
                            'text' => 'text-red-500',
                            'border' => 'border-red-200 dark:border-red-800',
                            'text_dark' => 'text-red-600',
                        ],
                        'cleaning' => [
                            'bg' => 'bg-orange-500',
                            'badge_bg' => 'bg-orange-50 dark:bg-orange-900/30',
                            'text' => 'text-orange-500',
                            'border' => 'border-orange-200 dark:border-orange-800',
                            'text_dark' => 'text-orange-600',
                        ],
                        default => [
                            'bg' => 'bg-zinc-500',
                            'badge_bg' => 'bg-zinc-50 dark:bg-zinc-900/30',
                            'text' => 'text-zinc-500',
                            'border' => 'border-zinc-200 dark:border-zinc-800',
                            'text_dark' => 'text-zinc-600',
                        ],
                    },
                    'guest' => $activeRes ? $activeRes->guest_name : null,
                    'details' => $unit->notes ?? 'Sin detalles adicionales',
                ];
            })
            ->keyBy('id')
            ->toArray();
    }

    public function openUnit($id)
    {
        $this->selectedUnitId = $id;
        $this->showReservationForm = false;
        $this->check_in = now()->toDateString();
        $this->reset(['guest_name', 'guest_phone', 'guest_email', 'nationality', 'license_plate', 'check_out', 'total_amount', 'errorMessage']);
        $this->check_in_time = '14:00';
        $this->check_out_time = '12:00';
        $this->nationality = 'Mexicana';
        $this->license_plate = '';
        \Flux::modal('unit-modal')->show();
    }

    public function updatedCheckIn()
    {
        $this->calculateAmount();
        $this->checkAvailability();
    }

    public function updatedCheckOut()
    {
        $this->calculateAmount();
        $this->checkAvailability();
    }

    public function checkAvailability()
    {
        $this->errorMessage = '';
        if ($this->selectedUnitId && $this->check_in && $this->check_out) {
            // Sin horas, solo validar solapamiento de fechas estándar
            $overlapping = \App\Models\Reservation::where('unit_id', $this->selectedUnitId)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) {
                    $query->where('check_in', '<', $this->check_out)
                          ->where('check_out', '>', $this->check_in);
                })->exists();
                
            if ($overlapping) {
                $this->errorMessage = 'La unidad seleccionada no está disponible en las fechas elegidas.';
            }
        }
    }

    public function calculateAmount()
    {
        if ($this->selectedUnitId && $this->check_in && $this->check_out) {
            $unit = Unit::find($this->selectedUnitId);
            if (!$unit) {
                $this->total_amount = 0;
                return;
            }

            $checkInDate = Carbon::parse($this->check_in);
            $checkOutDate = Carbon::parse($this->check_out);

            // Sin horas, calcular solo por días si el check-out es posterior
            if ($checkOutDate->lessThanOrEqualTo($checkInDate)) {
                $this->total_amount = 0;
                return;
            }
            $nights = $checkInDate->diffInDays($checkOutDate);
            $this->total_amount = $nights * $unit->price_per_day;
        } else {
            $this->total_amount = 0;
        }
    }

    public function createReservation()
    {
        $this->validate([
            'guest_name' => 'required|string|max:120',
            'guest_phone' => 'nullable|string|max:20',
            'guest_email' => 'nullable|email|max:120',
            'nationality' => 'required|string|max:50',
            'license_plate' => 'nullable|string|max:30',
            'check_in' => 'required|date|before:check_out',
            'check_out' => 'required|date|after:check_in',
        ], [
            'guest_name.required' => 'El nombre del huésped es obligatorio.',
            'guest_name.max' => 'El nombre no debe exceder 120 caracteres.',
            'guest_phone.max' => 'El teléfono no debe exceder 20 caracteres.',
            'guest_email.email' => 'El correo electrónico debe ser una dirección válida.',
            'guest_email.max' => 'El correo electrónico no debe exceder 120 caracteres.',
            'nationality.required' => 'La nacionalidad es obligatoria.',
            'nationality.max' => 'La nacionalidad no debe exceder 50 caracteres.',
            'license_plate.max' => 'Las placas no deben exceder 30 caracteres.',
            'check_in.required' => 'La fecha de check-in es obligatoria.',
            'check_in.date' => 'La fecha de check-in debe ser válida.',
            'check_in.before' => 'La fecha de check-in debe ser anterior al check-out.',
            'check_out.required' => 'La fecha de check-out es obligatoria.',
            'check_out.date' => 'La fecha de check-out debe ser válida.',
            'check_out.after' => 'La fecha de check-out debe ser posterior al check-in.',
        ]);

        $this->checkAvailability();
        if ($this->errorMessage) {
            $this->dispatch('swal-error', $this->errorMessage);
            return;
        }

        try {
            $this->calculateAmount();

            \App\Models\Reservation::create([
                'unit_id' => $this->selectedUnitId,
                'guest_name' => $this->guest_name,
                'guest_phone' => $this->guest_phone ?: null,
                'guest_email' => $this->guest_email ?: null,
                'nationality' => $this->nationality,
                'license_plate' => $this->license_plate ?: null,
                'check_in' => $this->check_in,
                'check_in_time' => '14:00',
                'check_out' => $this->check_out,
                'check_out_time' => '12:00',
                'status' => 'checked_in',
                'total_amount' => $this->total_amount ?: 0,
            ]);

            $unit = Unit::find($this->selectedUnitId);
            if ($unit) {
                $unit->update(['status' => 'occupied']);
            }

            $this->reset(['guest_name', 'guest_phone', 'guest_email', 'nationality', 'license_plate', 'check_out', 'total_amount', 'errorMessage']);
            $this->nationality = 'Mexicana';
            $this->license_plate = '';
            $this->loadData();
            $this->dispatch('swal-success', ['title' => 'Reservación creada', 'message' => 'La reservación se ha registrado exitosamente.']);
            \Flux::modal('unit-modal')->close();
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo crear la reservación. Intenta de nuevo.');
        }
    }

            $unit = Unit::find($this->selectedUnitId);
            if ($unit) {
                $unit->update(['status' => 'occupied']);
            }

            $this->loadData();
            $this->dispatch('swal-success', ['title' => 'Reservación creada', 'message' => 'La reservación se ha registrado exitosamente.']);
            \Flux::modal('unit-modal')->close();
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo crear la reservación. Intenta de nuevo.');
        }
    }

    public function markAs($status)
    {
        if ($this->selectedUnitId) {
            try {
                $unit = Unit::find($this->selectedUnitId);
                if ($unit) {
                    if ($status === 'available' || $status === 'cleaning') {
                        \App\Models\Reservation::where('unit_id', $this->selectedUnitId)
                            ->where('status', 'checked_in')
                            ->update(['status' => 'checked_out']);
                    }

                    $unit->update(['status' => $status]);
                    $this->loadData();

                    $statusLabels = [
                        'available' => 'Disponible',
                        'occupied' => 'Ocupado',
                        'cleaning' => 'Limpieza',
                    ];
                    $this->dispatch('swal-success', [
                        'title' => 'Estado actualizado',
                        'message' => 'Unidad marcada como ' . ($statusLabels[$status] ?? $status) . '.',
                    ]);
                }
            } catch (\Exception $e) {
                $this->dispatch('swal-error', 'No se pudo actualizar el estado de la unidad. Intenta de nuevo.');
            }
        }
    }
}; ?>

<div class="space-y-8">
    
        {{-- Header --}}
    <div class="mt-10 mb-8 flex items-end justify-between">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Inventario</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-1">Resumen para {{ now()->translatedFormat('l, d \d\e F Y') }}</p>
        </div>
    </div>

    {{-- Pendientes de limpieza alert --}}
    @php
        $cleaningUnits = collect($units)->filter(fn($u) => $u['status_raw'] === 'cleaning');
    @endphp
    @if ($cleaningUnits->isNotEmpty())
        <div
            class="bg-[#fdfcf7] border border-[#e8e7df] dark:bg-zinc-900/20 dark:border-zinc-800 rounded-3xl p-5 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-orange-50 dark:bg-orange-900/30 text-orange-500 rounded-2xl shrink-0">
                    <flux:icon name="sparkles" class="size-6" />
                </div>
                <div>
                    <h3 class="text-zinc-800 dark:text-zinc-300 font-bold text-lg">Unidades pendientes de limpieza</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm mt-1">Hay {{ $cleaningUnits->count() }} unidad(es)
                        que requieren servicio de limpieza.</p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($cleaningUnits as $cu)
                            <button wire:click="openUnit({{ $cu['id'] }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-zinc-800 border border-orange-200 dark:border-orange-700 text-orange-700 dark:text-orange-400 text-xs font-bold rounded-xl hover:bg-orange-100 dark:hover:bg-orange-900/50 transition-colors">
                                {{ $cu['name'] }}
                                <flux:icon name="chevron-right" class="size-3" />
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @php
            $tipos = [
                'all' => 'Todas',
                'bungalow' => 'Bungalows',
                'rv' => 'RV Spots',
                'camping' => 'Camping',
            ];
        @endphp
        @foreach ($tipos as $key => $label)
            <button wire:click="$set('filtro', '{{ $key }}')"
                class="rounded-full px-5 py-1.5 text-sm font-bold border transition
                {{ $filtro === $key
                    ? 'bg-[#556B46] text-white border-[#556B46]'
                    : 'bg-transparent text-zinc-600 border-zinc-300 hover:bg-zinc-100 dark:text-zinc-400 dark:border-zinc-600 dark:hover:bg-zinc-800' }}">
                {{ $label }}
                ({{ $key === 'all' ? count($units) : collect($units)->filter(fn($u) => strtolower($u['type']) === $key)->count() }})
            </button>
        @endforeach
    </div>

    <!-- Inventory Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse(collect($units)->filter(fn($u) => $filtro === 'all' || strtolower($u['type']) === $filtro) as $unit)
            <div wire:click="openUnit({{ $unit['id'] }})"
                class="cursor-pointer bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl p-4 shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                <!-- Thumbnail Area -->
                <div
                    class="relative w-full aspect-[4/3] bg-[#e8e7df] dark:bg-zinc-800 rounded-[1.5rem] flex items-center justify-center mb-5 overflow-hidden">
                    @if($unit['image'])
                        <img src="{{ asset('storage/' . $unit['image']) }}" class="absolute inset-0 w-full h-full object-cover" />
                    @else
                        <flux:icon name="home" class="size-16 text-zinc-300 dark:text-zinc-600" variant="outline"
                            stroke-width="1.5" />
                    @endif

                    <!-- Status dot -->
                    <div class="absolute top-4 right-4">
                        <div
                            class="size-4 rounded-full {{ $unit['color_classes']['bg'] }} border-[3px] border-[#e8e7df] dark:border-zinc-800">
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-2 pb-2">
                    <h3 class="text-xl font-semibold text-zinc-800 dark:text-white leading-tight">{{ $unit['name'] }}
                    </h3>
                    <p class="text-[15px] text-zinc-500 dark:text-zinc-400">{{ $unit['type'] }}</p>

                    <!-- Badge -->
                    <div class="mt-3">
                        <span
                            class="inline-block px-3 py-1 {{ $unit['color_classes']['badge_bg'] }} {{ $unit['color_classes']['text'] }} text-sm font-medium rounded-xl">
                            {{ $unit['status'] }}
                        </span>
                    </div>

                    <!-- Divider -->
                    <div class="w-full h-px bg-zinc-100 dark:bg-zinc-800 my-4"></div>

                    <!-- Info -->
                    <div class="space-y-1">
                        @if ($unit['status'] === 'Ocupado' && $unit['guest'])
                            <p class="text-[13px] text-zinc-500 dark:text-zinc-400">Huésped:</p>
                            <p class="font-semibold text-zinc-800 dark:text-white text-[15px]">{{ $unit['guest'] }}</p>
                        @endif

                        <p class="text-[13px] text-zinc-500 dark:text-zinc-400 {{ $unit['guest'] ? 'mt-3' : '' }}">
                            {{ $unit['details'] }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 text-center">
                <flux:icon name="archive-box" class="size-16 text-zinc-200 mx-auto mb-4" />
                <h3 class="text-xl font-bold text-zinc-900 dark:text-white">No hay unidades registradas</h3>
                <p class="text-zinc-500 mt-2">Ejecuta los seeders o agrega unidades manualmente.</p>
            </div>
        @endforelse
    </div>

    <!-- Modal Unit Detail -->
    <flux:modal name="unit-modal" class="md:w-full md:max-w-md">
        @if ($selectedUnitId && isset($units[$selectedUnitId]))
            @php $su = $units[$selectedUnitId]; @endphp
            <div class="p-6">
                <!-- Header -->
                <div class="mb-4">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $su['name'] }}</h2>
                </div>

                <!-- Image Area -->
                <div
                    class="relative w-full aspect-[16/9] bg-[#e8e7df] dark:bg-zinc-800 rounded-2xl flex items-center justify-center mb-6 overflow-hidden">
                    @if($su['image'])
                        <img src="{{ asset('storage/' . $su['image']) }}" class="absolute inset-0 w-full h-full object-cover" />
                    @else
                        <flux:icon name="home" class="size-16 text-zinc-300 dark:text-zinc-600" variant="outline"
                            stroke-width="1.5" />
                    @endif
                    <div class="absolute top-4 right-4">
                        <div
                            class="size-4 rounded-full {{ $su['color_classes']['bg'] }} border-[3px] border-[#e8e7df] dark:border-zinc-800">
                        </div>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Tipo</p>
                        <p class="font-bold text-zinc-900 dark:text-white">{{ $su['type'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Estado</p>
                        <span
                            class="inline-block px-3 py-0.5 border {{ $su['color_classes']['border'] }} {{ $su['color_classes']['badge_bg'] }} {{ $su['color_classes']['text_dark'] }} font-semibold text-sm rounded-full">
                            {{ $su['status'] }}
                        </span>
                    </div>
                </div>

                <div class="mb-8">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Especificaciones</p>
                    <p class="text-zinc-800 dark:text-zinc-200">{{ $su['details'] }}</p>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    @if(!$showReservationForm)
                        @if(auth()->user()->hasRole('receptionist') || auth()->user()->hasRole('admin'))

                        <p class="text-xs font-bold text-zinc-400 uppercase tracking-widest mb-1">Acciones Rápidas</p>

                        @if ($su['status_raw'] !== 'available')
                            <button wire:click="markAs('available')" x-on:click="$flux.modal('unit-modal').close()"
                                class="w-full py-3 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 font-bold rounded-xl transition-colors flex items-center justify-center gap-2">
                                <flux:icon name="check-circle" class="size-5" />
                                Marcar como Disponible / Limpio
                            </button>
                        @endif

                        @if ($su['status_raw'] !== 'cleaning')
                            <button wire:click="markAs('cleaning')" x-on:click="$flux.modal('unit-modal').close()"
                                class="w-full py-3 bg-orange-50 text-orange-600 hover:bg-orange-100 dark:bg-orange-900/30 dark:hover:bg-orange-900/50 font-bold rounded-xl transition-colors flex items-center justify-center gap-2">
                                <flux:icon name="sparkles" class="size-5" />
                                Marcar para Limpieza
                            </button>
                        @endif
                        @endif {{-- fin rol --}}
                        <div class="flex gap-3 mt-2">
                            @if ($su['status_raw'] === 'available')
                                <button wire:click="$set('showReservationForm', true)"
                                    class="flex-1 py-3 bg-[#4a5d41] text-white hover:bg-[#3a4a34] font-bold rounded-xl transition-colors shadow-lg shadow-brand-green/10">
                                    Nueva Reservación
                                </button>
                            @endif
                            <flux:modal.close class="flex-1">
                                <button type="button"
                                    class="w-full py-3 bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 font-bold rounded-xl transition-colors">
                                    Cerrar
                                </button>
                            </flux:modal.close>
                        </div>
                    @else
                        <!-- Reservation Form -->
                        <div class="space-y-4">
                            <p class="text-xs font-bold text-zinc-400 uppercase tracking-widest mb-2">Crear Reservación Activa</p>

                            @if($errorMessage)
                                <div class="p-4 mb-2 text-sm font-semibold text-red-800 rounded-xl bg-red-50 border border-red-100 dark:bg-red-900/30 dark:border-red-800/50 dark:text-red-400 flex items-center gap-3">
                                    <flux:icon name="exclamation-circle" class="size-5" />
                                    {{ $errorMessage }}
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Nombre del Huésped</label>
                                <input type="text" wire:model="guest_name" placeholder="Ej. Juan Pérez" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2 px-3 text-sm">
                                @error('guest_name') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Check-in (Fecha)</label>
                                    <input type="date" wire:model.live="check_in" min="{{ date('Y-m-d') }}" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2 px-3 text-sm">
                                    @error('check_in') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Check-out (Fecha)</label>
                                    <input type="date" wire:model.live="check_out" min="{{ $check_in ?: date('Y-m-d') }}" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2 px-3 text-sm">
                                    @error('check_out') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Teléfono (Opcional)</label>
                                <input type="tel" wire:model="guest_phone" placeholder="Ej. +52 55 1234 5678" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2 px-3 text-sm">
                                @error('guest_phone') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Correo electrónico (Opcional)</label>
                                <input type="email" wire:model="guest_email" placeholder="Ej. cliente@correo.com" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2 px-3 text-sm">
                                @error('guest_email') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Nacionalidad</label>
                                    <select wire:model="nationality" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2 px-3 text-sm">
                                        <option value="">Seleccione...</option>
                                        @foreach($nationalities as $nat)
                                            <option value="{{ $nat }}">{{ $nat }}</option>
                                        @endforeach
                                    </select>
                                    @error('nationality') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Placas (Opcional)</label>
                                    <input type="text" wire:model="license_plate" placeholder="Ej. ABC-1234" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2 px-3 text-sm">
                                    @error('license_plate') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            @php
                                $showBreakdown = $this->selectedUnitId && $this->check_in && $this->check_out && $this->total_amount > 0;
                                $unitForLabel = $showBreakdown ? \App\Models\Unit::find($this->selectedUnitId) : null;
                            @endphp
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30 p-4 rounded-2xl flex justify-between items-center mt-6">
                                <div>
                                    <span class="block text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Estimado</span>
                                    @if($showBreakdown)
                                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ \Carbon\Carbon::parse($this->check_in)->diffInDays($this->check_out) }} noche(s) × ${{ number_format($unitForLabel?->price_per_day ?? 0, 2) }}
                                        </span>
                                    @else
                                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">Monto por estadía</span>
                                    @endif
                                </div>
                                <span class="text-2xl font-black text-emerald-700 dark:text-emerald-300">${{ number_format($total_amount, 2) }}</span>
                            </div>

                            <div class="flex gap-3 mt-4">
                                <button wire:click="$set('showReservationForm', false)" type="button"
                                    class="flex-1 py-3 bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 font-bold rounded-xl transition-colors">
                                    Cancelar
                                </button>
                                <button wire:click="createReservation" type="button"
                                    class="flex-1 py-3 bg-[#4a5d41] text-white hover:bg-[#3a4a34] font-bold rounded-xl transition-colors shadow-lg shadow-brand-green/10 flex items-center justify-center gap-2"
                                    {{ $errorMessage ? 'disabled' : '' }}>
                                    <flux:icon name="check" class="size-5" />
                                    Confirmar
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>
</div>
