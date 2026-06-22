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
    
    public function updatedUnitId()
    {
        $this->calculateAmount();
        $this->checkAvailability();
    }
    
    public function checkAvailability()
    {
        $this->errorMessage = '';
        if ($this->unit_id && $this->check_in && $this->check_out) {
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
            $in = Carbon::parse($this->check_in);
            $out = Carbon::parse($this->check_out);
            
            if ($out->greaterThan($in)) {
                $nights = $in->diffInDays($out);
                
                $unit = Unit::find($this->unit_id);
                $rate = 0;
                if ($unit) {
                    $rate = $unit->price_per_day;
                }
                
                $this->total_amount = $nights * $rate;
            } else {
                $this->total_amount = 0;
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
            'check_in' => 'required|date|before:check_out',
            'check_in_time' => $this->status === 'confirmed' ? 'required|date_format:H:i' : 'nullable|date_format:H:i',
            'check_out' => 'required|date|after:check_in',
            'check_out_time' => 'nullable|date_format:H:i',
            'status' => 'required|in:pending,confirmed',
        ]);
        
        $this->checkAvailability();
        if ($this->errorMessage) {
            return;
        }
        
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
        $this->status = 'confirmed'; // reset to default
        $this->showModal = false;
        
        session()->flash('message', 'Reserva registrada con éxito.');
        $this->dispatch('reservation-created');
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
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Unidad Disponible</label>
                        <select wire:model.live="unit_id" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                            <option value="">Seleccione una unidad...</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @if($unit->status !== 'available') disabled @endif>
                                    {{ $unit->name }} ({{ ucfirst($unit->type) }}) @if($unit->status !== 'available') - No disponible @endif
                                </option>
                            @endforeach
                        </select>
                        @error('unit_id') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Check-in (Fecha)</label>
                            <input type="date" wire:model.live="check_in" min="{{ date('Y-m-d') }}" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                            @error('check_in') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Check-out (Fecha)</label>
                            <input type="date" wire:model.live="check_out" min="{{ $check_in ? date('Y-m-d', strtotime($check_in . ' +1 day')) : date('Y-m-d', strtotime('+1 day')) }}" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                            @error('check_out') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
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
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Hora de Salida</label>
                            <input type="time" wire:model="check_out_time" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                            @error('check_out_time') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    @if($status === 'confirmed')
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Hora de Entrada</label>
                        <input type="time" wire:model="check_in_time" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-transparent dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                        @error('check_in_time') <span class="text-red-500 text-xs font-semibold mt-1 inline-block">{{ $message }}</span> @enderror
                    </div>
                    @endif

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
                    
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30 p-5 rounded-2xl flex justify-between items-center mt-6">
                        <div>
                            <span class="block text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Estimado</span>
                            <span class="block text-sm text-zinc-500 dark:text-zinc-400">Monto por noches</span>
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
