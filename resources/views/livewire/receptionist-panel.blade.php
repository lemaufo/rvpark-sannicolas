<?php

use Livewire\Volt\Component;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\OperationalStatus;
use Livewire\Attributes\On;

new class extends Component {
    public $reservations;
    public $units;

    public $guest_name = '';
    public $guest_phone = '';
    public $unit_id = '';
    public $check_in = '';
    public $check_out = '';
    public $total_amount = '';

    public function mount()
    {
        $this->loadData();
    }

    #[On('reservation-created')]
    public function loadData()
    {
        $this->reservations = Reservation::with('unit')->orderBy('check_in')->get();
        $this->units = Unit::all();
    }

    public function createReservation()
    {
        $this->validate([
            'guest_name' => 'required|string',
            'unit_id' => 'required|exists:units,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        Reservation::create([
            'guest_name' => $this->guest_name,
            'guest_phone' => $this->guest_phone,
            'unit_id' => $this->unit_id,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'status' => 'pending',
            'total_amount' => $this->total_amount ?: 0,
        ]);

        $this->reset(['guest_name', 'guest_phone', 'unit_id', 'check_in', 'check_out', 'total_amount']);
        $this->loadData();
        \Flux::modal('new-reservation')->close();
    }

    public function checkIn($id)
    {
        $res = Reservation::find($id);
        if ($res && in_array($res->status, ['pending', 'confirmed'])) {
            $res->update(['status' => 'checked_in']);

            $unit = Unit::find($res->unit_id);
            if ($unit) {
                $unit->update(['status' => 'occupied']);
                OperationalStatus::create([
                    'unit_id' => $unit->id,
                    'status' => 'occupied',
                    'user_id' => auth()->id(),
                    'changed_at' => now()
                ]);
            }
            $this->loadData();
        }
    }

    public function checkOut($id)
    {
        $res = Reservation::find($id);
        if ($res && $res->status == 'checked_in') {
            $res->update(['status' => 'checked_out']);

            $unit = Unit::find($res->unit_id);
            if ($unit) {
                $unit->update(['status' => 'cleaning']);
                OperationalStatus::create([
                    'unit_id' => $unit->id,
                    'status' => 'cleaning',
                    'user_id' => auth()->id(),
                    'changed_at' => now()
                ]);
            }
            $this->loadData();
        }
    }
}; ?>

<div class="space-y-8">
    {{-- Top Action --}}
    <div class="flex justify-end mb-6 -mt-16 relative z-10">
        <button x-data x-on:click="$flux.modal('new-reservation').show()" type="button" class="bg-[#4a5d41] text-white px-6 py-3 rounded-2xl font-bold shadow-xl shadow-brand-green/20 hover:scale-[1.02] transition-all duration-200 flex items-center gap-2.5">
            <flux:icon name="plus" class="size-5" />
            <span>Nueva Reserva</span>
        </button>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
        @php
            $todayArrivals = collect($reservations)->filter(fn($r) => \Carbon\Carbon::parse($r->check_in)->isToday() && in_array($r->status, ['pending', 'confirmed']))->count();
            $todayDepartures = collect($reservations)->filter(fn($r) => \Carbon\Carbon::parse($r->check_out)->isToday() && $r->status === 'checked_in')->count();
            $availableUnits = collect($units)->filter(fn($u) => $u->status === 'available')->count();
        @endphp
        
        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-[2rem] p-7 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-zinc-400 text-[13px] font-bold uppercase tracking-wider">Entradas Hoy</p>
                    <h3 class="text-3xl font-black text-zinc-900 dark:text-white mt-2">{{ $todayArrivals }}</h3>
                </div>
                <div class="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-2xl text-blue-500">
                    <flux:icon name="arrow-right-start-on-rectangle" class="size-7" />
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-[2rem] p-7 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-zinc-400 text-[13px] font-bold uppercase tracking-wider">Salidas Hoy</p>
                    <h3 class="text-3xl font-black text-zinc-900 dark:text-white mt-2">{{ $todayDepartures }}</h3>
                </div>
                <div class="p-4 bg-orange-50 dark:bg-orange-900/30 rounded-2xl text-orange-500">
                    <flux:icon name="arrow-left-start-on-rectangle" class="size-7" />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-[2rem] p-7 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-zinc-400 text-[13px] font-bold uppercase tracking-wider">Disponibles</p>
                    <h3 class="text-3xl font-black text-zinc-900 dark:text-white mt-2">{{ $availableUnits }} <span class="text-lg text-zinc-400">/ {{ count($units) }}</span></h3>
                </div>
                <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl text-emerald-500">
                    <flux:icon name="home" class="size-7" />
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs or Sections for Operations --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        {{-- Entradas Pendientes --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl p-6 shadow-sm">
            <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-4 flex items-center gap-2">
                <flux:icon name="arrow-right-start-on-rectangle" class="size-5 text-blue-500" />
                Llegadas Esperadas / Pendientes
            </h3>
            
            <div class="space-y-4">
                @forelse(collect($reservations)->filter(fn($r) => in_array($r->status, ['pending', 'confirmed'])) as $res)
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700">
                        <div>
                            <p class="font-bold text-zinc-900 dark:text-white">{{ $res->guest_name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $res->unit->name ?? 'N/A' }} • Llegada: {{ \Carbon\Carbon::parse($res->check_in)->format('d/m/Y') }}</p>
                            @if($res->status == 'pending')
                                <span class="mt-1 inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-500">Pendiente de Confirmar</span>
                            @else
                                <span class="mt-1 inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-500">Confirmada</span>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="checkIn({{ $res->id }})" class="px-4 py-2 bg-[#4a5d41] hover:bg-[#3d4d35] text-white text-sm font-bold rounded-xl transition-colors shadow-sm">
                                Check-In
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-zinc-500 text-sm py-4 text-center">No hay llegadas pendientes hoy.</p>
                @endforelse
            </div>
        </div>

        {{-- Salidas y Activas --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl p-6 shadow-sm">
            <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-4 flex items-center gap-2">
                <flux:icon name="arrow-left-start-on-rectangle" class="size-5 text-orange-500" />
                Huéspedes Activos / Salidas
            </h3>
            
            <div class="space-y-4">
                @forelse(collect($reservations)->filter(fn($r) => $r->status === 'checked_in') as $res)
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700">
                        <div>
                            <p class="font-bold text-zinc-900 dark:text-white">{{ $res->guest_name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $res->unit->name ?? 'N/A' }} • Salida: {{ \Carbon\Carbon::parse($res->check_out)->format('d/m/Y') }}</p>
                        </div>
                        <button wire:click="checkOut({{ $res->id }})" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold rounded-xl transition-colors shadow-sm">
                            Check-Out
                        </button>
                    </div>
                @empty
                    <p class="text-zinc-500 text-sm py-4 text-center">No hay huéspedes activos en este momento.</p>
                @endforelse
            </div>
        </div>
    </div>

    <flux:modal name="new-reservation" class="md:w-full md:max-w-xl">
        @livewire('receptionist.new-reservation')
    </flux:modal>
</div>