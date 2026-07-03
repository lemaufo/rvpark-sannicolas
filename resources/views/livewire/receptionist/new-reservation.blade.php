<?php

use Livewire\Volt\Component;
use App\Models\Unit;
use App\Models\Reservation;
use Carbon\Carbon;

new class extends Component {
    public $showModal = false;
    
    public $unit_id = '';
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
    
    public $units = [];
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
        $this->units = Unit::all();
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
    
    public function updatedUnitId()
    {
        $this->calculateAmount();
        $this->checkAvailability();
    }
    
    public function checkAvailability()
    {
        $this->errorMessage = '';
        if ($this->unit_id && $this->check_in && $this->check_out) {
            // Sin horas, solo validar solapamiento de fechas estándar
            $overlapping = Reservation::where('unit_id', $this->unit_id)
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
        if ($this->unit_id && $this->check_in && $this->check_out) {
            $unit = Unit::find($this->unit_id);
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
    
    public function submit()
    {
        $this->validate([
            'unit_id' => 'required|exists:units,id',
            'guest_name' => 'required|string|max:120',
            'guest_phone' => 'nullable|string|max:20',
            'guest_email' => 'nullable|email|max:120',
            'nationality' => 'required|string|max:50',
            'license_plate' => 'nullable|string|max:30',
            'check_in' => 'required|date|before:check_out',
            'check_out' => 'required|date|after:check_in',
        ], [
            'unit_id.required' => 'Selecciona una unidad.',
            'unit_id.exists' => 'La unidad seleccionada no es válida.',
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
            
            Reservation::create([
                'unit_id' => $this->unit_id,
                'guest_name' => $this->guest_name,
                'guest_phone' => $this->guest_phone ?: null,
                'guest_email' => $this->guest_email ?: null,
                'nationality' => $this->nationality,
                'license_plate' => $this->license_plate ?: null,
                'check_in' => $this->check_in,
                'check_in_time' => '14:00',
                'check_out' => $this->check_out,
                'check_out_time' => '12:00',
                'status' => 'pending',
                'total_amount' => $this->total_amount
            ]);
            
            $this->reset(['unit_id', 'guest_name', 'guest_phone', 'guest_email', 'nationality', 'license_plate', 'check_in', 'check_out', 'total_amount']);
            $this->check_in_time = '14:00';
            $this->check_out_time = '12:00';
            $this->nationality = 'Mexicana';
            $this->license_plate = '';
            $this->showModal = false;
            
            $this->dispatch('swal-success', ['title' => 'Reserva registrada', 'message' => 'La reserva se ha creado exitosamente.']);
            $this->dispatch('reservation-created');
            \Flux::modal('new-reservation')->close();
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo registrar la reserva. Intenta de nuevo.');
        }
    }
}; ?>

<div>
    <div class="px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 flex justify-between items-center bg-zinc-50 dark:bg-zinc-900/50">
        <h2 class="text-xl font-black text-zinc-900 dark:text-white flex items-center gap-2">
            <flux:icon name="calendar-days" class="size-6 text-emerald-500" />
            Nueva Reserva
        </h2>
    </div>
    
    <div class="p-6">
        @if (session()->has('message'))
            <div class="p-4 mb-5 text-sm font-semibold text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-100 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400 flex items-center gap-3">
                <flux:icon name="check-circle" class="size-5" />
                {{ session('message') }}
            </div>
        @endif
        
        @if($errorMessage)
            <div class="p-4 mb-5 text-sm font-semibold text-red-800 rounded-xl bg-red-50 border border-red-100 dark:bg-red-900/30 dark:border-red-800/50 dark:text-red-400 flex items-center gap-3">
                <flux:icon name="exclamation-circle" class="size-5" />
                {{ $errorMessage }}
            </div>
        @endif

        <form wire:submit="submit">
            <div class="mb-6">
                <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Unidad Disponible</label>
                <select wire:model.live="unit_id" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2.5">
                    <option value="">Seleccione una unidad...</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">
                            {{ $unit->name }} ({{ ucfirst($unit->type) }}) @if($unit->status !== 'available') - ({{ $unit->status === 'occupied' ? 'Ocupada' : 'En limpieza' }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('unit_id') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Check-in (Fecha)</label>
                    <input type="date" wire:model.live="check_in" min="{{ date('Y-m-d') }}" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2.5">
                    @error('check_in') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Check-out (Fecha)</label>
                    <input type="date" wire:model.live="check_out" min="{{ $check_in ?: date('Y-m-d') }}" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2.5">
                    @error('check_out') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Horarios de Entrada y Salida (Vista informativa) -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Hora de Entrada</label>
                    <div class="w-full rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 text-zinc-500 dark:text-zinc-400 py-2.5 px-3.5 text-sm font-medium flex items-center gap-2 select-none">
                        <flux:icon name="clock" class="size-4 text-zinc-400 shrink-0" />
                        <span>2:00 PM</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Hora de Salida</label>
                    <div class="w-full rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 text-zinc-500 dark:text-zinc-400 py-2.5 px-3.5 text-sm font-medium flex items-center gap-2 select-none">
                        <flux:icon name="clock" class="size-4 text-zinc-400 shrink-0" />
                        <span>12:00 PM</span>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Nombre del Huésped</label>
                <input type="text" wire:model="guest_name" placeholder="Ej. Juan Pérez" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2.5">
                @error('guest_name') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Teléfono (Opcional)</label>
                <input type="tel" wire:model="guest_phone" placeholder="Ej. +52 55 1234 5678" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2.5">
                @error('guest_phone') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Correo electrónico (Opcional)</label>
                <input type="email" wire:model="guest_email" placeholder="Ej. cliente@correo.com" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2.5">
                @error('guest_email') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Nacionalidad</label>
                    <select wire:model="nationality" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2.5 px-3">
                        <option value="">Seleccione...</option>
                        @foreach($nationalities as $nat)
                            <option value="{{ $nat }}">{{ $nat }}</option>
                        @endforeach
                    </select>
                    @error('nationality') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Placas (Opcional)</label>
                    <input type="text" wire:model="license_plate" placeholder="Ej. ABC-1234" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors py-2.5 px-3">
                    @error('license_plate') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                </div>
            </div>
            
            @php
                $showBreakdown = $this->unit_id && $this->check_in && $this->check_out && $this->total_amount > 0;
                $unitForLabel = $showBreakdown ? \App\Models\Unit::find($this->unit_id) : null;
            @endphp
            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30 p-5 rounded-2xl flex justify-between items-center mb-6 mt-8">
                <div>
                    <span class="block text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Estimado</span>
                    @if($showBreakdown)
                        <span class="block text-sm text-zinc-500 dark:text-zinc-400">
                            {{ \Carbon\Carbon::parse($this->check_in)->diffInDays($this->check_out) }} noche(s) × ${{ number_format($unitForLabel?->price_per_day ?? 0, 2) }}
                        </span>
                    @else
                        <span class="block text-sm text-zinc-500 dark:text-zinc-400">Monto por estadía</span>
                    @endif
                </div>
                <span class="text-3xl font-black text-emerald-700 dark:text-emerald-300">${{ number_format($total_amount, 2) }}</span>
            </div>

            <div class="pt-6 flex justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800 mt-8">
                <flux:modal.close>
                    <button type="button" class="px-6 py-2.5 rounded-xl font-bold text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        Cancelar
                    </button>
                </flux:modal.close>
                <button type="submit" class="px-6 py-2.5 bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 rounded-xl font-bold hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-all shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2" {{ $errorMessage ? 'disabled' : '' }}>
                    <flux:icon name="check" class="size-5" />
                    Registrar Reserva
                </button>
            </div>
        </form>
    </div>
</div>
