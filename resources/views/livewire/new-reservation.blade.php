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
    public $check_in = '';
    public $check_in_time = '';
    public $check_out = '';
    public $check_out_time = '';
    public $status = 'confirmed';
    public $total_amount = 0;
    
    public $units = [];
    public $errorMessage = '';
    
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
    
    public function updatedCheckInTime()
    {
        $this->calculateAmount();
        $this->checkAvailability();
    }
    
    public function updatedCheckOutTime()
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
            // Si es por horas, comparar tanto fechas como horas
            if ($this->check_in_time && $this->check_out_time) {
                $checkInFull = Carbon::parse($this->check_in . ' ' . $this->check_in_time);
                $checkOutFull = Carbon::parse($this->check_out . ' ' . $this->check_out_time);

                $reservations = Reservation::where('unit_id', $this->unit_id)
                    ->where('status', '!=', 'cancelled')
                    ->get();

                foreach ($reservations as $res) {
                    $resIn = Carbon::parse($res->check_in . ' ' . ($res->check_in_time ?: '00:00'));
                    $resOut = Carbon::parse($res->check_out . ' ' . ($res->check_out_time ?: '23:59'));

                    if ($resIn->lessThan($checkOutFull) && $resOut->greaterThan($checkInFull)) {
                        $this->errorMessage = 'La unidad seleccionada no está disponible en el horario elegido.';
                        return;
                    }
                }
            } else {
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

            // Si tenemos hora de entrada y salida, calculamos preciso
            if ($this->check_in_time && $this->check_out_time) {
                $checkInFull = Carbon::parse($this->check_in . ' ' . $this->check_in_time);
                $checkOutFull = Carbon::parse($this->check_out . ' ' . $this->check_out_time);

                if ($checkOutFull->lessThanOrEqualTo($checkInFull)) {
                    $this->total_amount = 0;
                    return;
                }

                $totalMinutes = $checkInFull->diffInMinutes($checkOutFull);
                $totalHours = (int) ceil($totalMinutes / 60);

                // Si es menos de 24 horas, cobrar por hora
                if ($totalHours < 24) {
                    $this->total_amount = $totalHours * $unit->price_per_hour;
                } else {
                    // Calcular por días + horas restantes
                    $days = intdiv($totalHours, 24);
                    $remainingHours = $totalHours % 24;
                    $this->total_amount = ($days * $unit->price_per_day) + ($remainingHours * $unit->price_per_hour);
                }
            } else {
                // Sin horas, calcular solo por días (tarifa estándar) si el check-out es posterior
                if ($checkOutDate->lessThanOrEqualTo($checkInDate)) {
                    $this->total_amount = 0;
                    return;
                }
                $nights = $checkInDate->diffInDays($checkOutDate);
                $this->total_amount = $nights * $unit->price_per_day;
            }
        } else {
            $this->total_amount = 0;
        }
    }
    
    public function submit()
    {
        $this->validate([
            'unit_id' => 'required|exists:units,id',
            'guest_name' => 'required|string|max:120',
            'guest_phone' => 'required|string|max:20',
            'check_in' => 'required|date|before_or_equal:check_out',
            'check_in_time' => $this->status === 'confirmed' ? 'required|date_format:H:i' : 'nullable|date_format:H:i',
            'check_out' => 'required|date|after_or_equal:check_in',
            'check_out_time' => 'nullable|date_format:H:i',
            'status' => 'required|in:pending,confirmed',
        ], [
            'unit_id.required' => 'Selecciona una unidad.',
            'unit_id.exists' => 'La unidad seleccionada no es válida.',
            'guest_name.required' => 'El nombre del huésped es obligatorio.',
            'guest_name.max' => 'El nombre no debe exceder 120 caracteres.',
            'guest_phone.required' => 'El teléfono del huésped es obligatorio.',
            'guest_phone.max' => 'El teléfono no debe exceder 20 caracteres.',
            'check_in.required' => 'La fecha de check-in es obligatoria.',
            'check_in.date' => 'La fecha de check-in debe ser válida.',
            'check_in.before_or_equal' => 'La fecha de check-in debe ser anterior o igual al check-out.',
            'check_in_time.required' => 'La hora de check-in es obligatoria para reservas confirmadas.',
            'check_in_time.date_format' => 'La hora de check-in debe tener formato HH:mm.',
            'check_out.required' => 'La fecha de check-out es obligatoria.',
            'check_out.date' => 'La fecha de check-out debe ser válida.',
            'check_out.after_or_equal' => 'La fecha de check-out debe ser posterior o igual al check-in.',
            'check_out_time.date_format' => 'La hora de check-out debe tener formato HH:mm.',
            'status.required' => 'El estatus es obligatorio.',
            'status.in' => 'El estatus debe ser Pendiente o Confirmada.',
        ]);

        // Validación adicional para reservaciones del mismo día
        if ($this->check_in === $this->check_out) {
            if (!$this->check_in_time || !$this->check_out_time) {
                $this->dispatch('swal-error', 'Para reservas del mismo día, debes especificar la hora de entrada y de salida.');
                return;
            }
            $checkInFull = Carbon::parse($this->check_in . ' ' . $this->check_in_time);
            $checkOutFull = Carbon::parse($this->check_out . ' ' . $this->check_out_time);
            if ($checkOutFull->lessThanOrEqualTo($checkInFull)) {
                $this->dispatch('swal-error', 'La hora de salida debe ser posterior a la hora de entrada.');
                return;
            }
        }
        
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
                'guest_phone' => $this->guest_phone,
                'check_in' => $this->check_in,
                'check_in_time' => $this->status === 'confirmed' ? ($this->check_in_time ?: null) : null,
                'check_out' => $this->check_out,
                'check_out_time' => $this->check_out_time ?: null,
                'status' => $this->status,
                'total_amount' => $this->total_amount
            ]);
            
            $this->reset(['unit_id', 'guest_name', 'guest_phone', 'check_in', 'check_in_time', 'check_out', 'check_out_time', 'status', 'total_amount']);
            $this->status = 'confirmed';
            $this->showModal = false;
            
            $this->dispatch('swal-success', ['title' => 'Reserva registrada', 'message' => 'La reserva se ha creado exitosamente.']);
            $this->dispatch('reservation-created');
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo registrar la reserva. Intenta de nuevo.');
        }
    }
}; ?>

<div>
    <!-- Button to trigger modal -->
    <button wire:click="$set('showModal', true)" class="px-5 py-2.5 bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 rounded-xl font-bold shadow-md hover:shadow-lg transition-all duration-300 flex items-center gap-2 transform hover:-translate-y-0.5">
        <flux:icon name="plus" class="size-5" />
        Nueva Reserva
    </button>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/60 backdrop-blur-sm transition-opacity duration-300">
        <div class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-2xl w-full max-w-lg overflow-hidden border border-zinc-200 dark:border-zinc-800 transform transition-all">
            <div class="px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 flex justify-between items-center bg-zinc-50 dark:bg-zinc-900/50">
                <h2 class="text-xl font-black text-zinc-900 dark:text-white flex items-center gap-2">
                    <flux:icon name="calendar-days" class="size-6 text-emerald-500" />
                    Nueva Reserva
                </h2>
                <button wire:click="$set('showModal', false)" class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-800 rounded-full transition-colors">
                    <flux:icon name="x-mark" class="size-5" />
                </button>
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

                <form wire:submit="submit" class="space-y-6">

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Check-in (Fecha)</label>
                            <input type="date" wire:model.live="check_in" min="{{ date('Y-m-d') }}" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                            @error('check_in') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Check-out (Fecha)</label>
                            <input type="date" wire:model.live="check_out" min="{{ $check_in ?: date('Y-m-d') }}" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                            @error('check_out') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Hora de Entrada</label>
                            <input type="time" wire:model.live="check_in_time" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                            @error('check_in_time') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Hora de Salida</label>
                            <input type="time" wire:model.live="check_out_time" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                            @error('check_out_time') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Estatus</label>
                            <select wire:model.live="status" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                                <option value="pending">Pendiente</option>
                                <option value="confirmed">Confirmada</option>
                            </select>
                            @error('status') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Unidad</label>
                            <select wire:model.live="unit_id" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                                <option value="">Seleccione una unidad...</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">
                                        {{ $unit->name }} ({{ ucfirst($unit->type) }}) - ${{ number_format($unit->price_per_day, 2) }}/día - ${{ number_format($unit->price_per_hour, 2) }}/hora @if($unit->status !== 'available') - ({{ $unit->status === 'occupied' ? 'Ocupada' : 'En limpieza' }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('unit_id') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Nombre del Huésped</label>
                        <input type="text" wire:model="guest_name" placeholder="Ej. Juan Pérez" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                        @error('guest_name') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Teléfono</label>
                        <input type="tel" wire:model="guest_phone" placeholder="Ej. +52 55 1234 5678" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                        @error('guest_phone') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                    </div>
                    
                    @php
                        $showBreakdown = $this->unit_id && $this->check_in && $this->check_out && $this->total_amount > 0;
                        $unitForLabel = $showBreakdown ? \App\Models\Unit::find($this->unit_id) : null;
                    @endphp
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30 p-5 rounded-2xl flex justify-between items-center mt-6">
                        <div>
                            <span class="block text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Estimado</span>
                            @if($showBreakdown && $this->check_in_time && $this->check_out_time)
                                @php
                                    $inFull = \Carbon\Carbon::parse($this->check_in . ' ' . $this->check_in_time);
                                    $outFull = \Carbon\Carbon::parse($this->check_out . ' ' . $this->check_out_time);
                                    $totalH = (int) ceil($inFull->diffInMinutes($outFull) / 60);
                                @endphp
                                @if($totalH < 24)
                                    <span class="block text-sm text-zinc-500 dark:text-zinc-400">{{ $totalH }} hora(s) × ${{ number_format($unitForLabel?->price_per_hour ?? 0, 2) }}/hora</span>
                                @else
                                    @php
                                        $days = intdiv($totalH, 24);
                                        $remH = $totalH % 24;
                                    @endphp
                                    <span class="block text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $days }} día(s) × ${{ number_format($unitForLabel?->price_per_day ?? 0, 2) }}
                                        @if($remH > 0)
                                            + {{ $remH }} hora(s) × ${{ number_format($unitForLabel?->price_per_hour ?? 0, 2) }}
                                        @endif
                                    </span>
                                @endif
                            @elseif($showBreakdown)
                                <span class="block text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ \Carbon\Carbon::parse($this->check_in)->diffInDays($this->check_out) }} noche(s) × ${{ number_format($unitForLabel?->price_per_day ?? 0, 2) }}
                                </span>
                            @else
                                <span class="block text-sm text-zinc-500 dark:text-zinc-400">Monto por estadía</span>
                            @endif
                        </div>
                        <span class="text-3xl font-black text-emerald-700 dark:text-emerald-300">${{ number_format($total_amount, 2) }}</span>
                    </div>

                    <div class="pt-6 flex justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800 mt-6">
                        <button type="button" wire:click="$set('showModal', false)" class="px-6 py-2.5 rounded-xl font-bold text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-2.5 bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 rounded-xl font-bold hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-all shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2" {{ $errorMessage ? 'disabled' : '' }}>
                            <flux:icon name="check" class="size-5" />
                            Registrar Reserva
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
