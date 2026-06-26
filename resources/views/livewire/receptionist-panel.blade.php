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
        ], [
            'guest_name.required' => 'El nombre del huésped es obligatorio.',
            'guest_name.string' => 'El nombre del huésped debe ser un texto.',
            'unit_id.required' => 'Selecciona una unidad.',
            'unit_id.exists' => 'La unidad seleccionada no es válida.',
            'check_in.required' => 'La fecha de check-in es obligatoria.',
            'check_in.date' => 'La fecha de check-in debe ser válida.',
            'check_out.required' => 'La fecha de check-out es obligatoria.',
            'check_out.date' => 'La fecha de check-out debe ser válida.',
            'check_out.after' => 'La fecha de check-out debe ser posterior al check-in.',
        ]);

        try {
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
            $this->dispatch('swal-success', ['title' => 'Reserva creada', 'message' => 'La reservación se ha registrado exitosamente.']);
            \Flux::modal('new-reservation')->close();
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo crear la reservación. Intenta de nuevo.');
        }
    }

    public function confirmReservation($id)
    {
        try {
            $res = Reservation::find($id);
            if ($res && $res->status == 'pending') {
                $service = app(\App\Services\ReservationService::class);
                $service->confirmReservation($res, auth()->id());
                
                $this->loadData();
                $this->dispatch('swal-success', ['title' => 'Reserva confirmada', 'message' => 'La reservación ha sido confirmada exitosamente.']);
            }
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo confirmar la reservación. Intenta de nuevo.');
        }
    }

    public function cancelReservation($id, $reason)
    {
        try {
            $reservation = Reservation::find($id);
            if ($reservation && $reservation->status !== 'cancelled') {
                $service = app(\App\Services\ReservationService::class);
                $service->cancelReservation($reservation, auth()->id(), $reason);
                $this->loadData();
                $this->dispatch('swal-success', ['title' => 'Reserva cancelada', 'message' => 'La reservación se ha cancelado exitosamente.']);
            } else {
                $this->dispatch('swal-error', 'No se pudo cancelar la reserva.');
            }
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo cancelar la reserva. Intenta de nuevo.');
        }
    }

    public function checkIn($id)
    {
        try {
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
                $this->dispatch('swal-success', ['title' => 'Check-In realizado', 'message' => 'El huésped ha sido registrado exitosamente.']);
            }
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo realizar el check-in. Intenta de nuevo.');
        }
    }

    public function checkOut($id)
    {
        try {
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
                $this->dispatch('swal-success', ['title' => 'Check-Out realizado', 'message' => 'El huésped ha dado salida exitosamente.']);
            }
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo realizar el check-out. Intenta de nuevo.');
        }
    }
}; ?>

<div class="space-y-8">
    {{-- Top Action --}}
    <div class="flex justify-end lg:mb-6 lg:-mt-16 max-lg:mt-0 max-lg:mb-4 relative z-10">
        <button x-data x-on:click="$flux.modal('new-reservation').show()" type="button" class="bg-[#4a5d41] text-white px-5 sm:px-6 py-3 rounded-2xl font-bold shadow-xl shadow-brand-green/20 hover:scale-[1.02] transition-all duration-200 flex items-center gap-2.5 text-sm sm:text-base w-full sm:w-auto justify-center">
            <flux:icon name="plus" class="size-5" />
            <span>Nueva Reserva</span>
        </button>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-3 gap-2 lg:gap-6 mb-8 lg:mb-10">
        @php
            $todayArrivals = collect($reservations)->filter(fn($r) => \Carbon\Carbon::parse($r->check_in)->isToday() && in_array($r->status, ['pending', 'confirmed']))->count();
            $todayDepartures = collect($reservations)->filter(fn($r) => \Carbon\Carbon::parse($r->check_out)->isToday() && $r->status === 'checked_in')->count();
            $availableUnits = collect($units)->filter(fn($u) => $u->status === 'available')->count();
        @endphp
        
        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-2xl lg:rounded-[2rem] p-3 lg:p-7 shadow-sm">
            <div class="flex flex-col-reverse lg:flex-row items-center lg:items-start justify-between gap-1 lg:gap-0 text-center lg:text-left">
                <div class="flex-1 min-w-0">
                    <p class="text-zinc-400 text-[9px] lg:text-[13px] font-bold uppercase tracking-wider truncate">Entradas</p>
                    <h3 class="text-xl lg:text-3xl font-black text-zinc-900 dark:text-white mt-0.5 lg:mt-2">{{ $todayArrivals }}</h3>
                </div>
                <div class="p-2 lg:p-4 bg-blue-50 dark:bg-blue-900/30 rounded-xl lg:rounded-2xl text-blue-500 shrink-0">
                    <flux:icon name="arrow-right-start-on-rectangle" class="size-5 lg:size-7" />
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-2xl lg:rounded-[2rem] p-3 lg:p-7 shadow-sm">
            <div class="flex flex-col-reverse lg:flex-row items-center lg:items-start justify-between gap-1 lg:gap-0 text-center lg:text-left">
                <div class="flex-1 min-w-0">
                    <p class="text-zinc-400 text-[9px] lg:text-[13px] font-bold uppercase tracking-wider truncate">Salidas</p>
                    <h3 class="text-xl lg:text-3xl font-black text-zinc-900 dark:text-white mt-0.5 lg:mt-2">{{ $todayDepartures }}</h3>
                </div>
                <div class="p-2 lg:p-4 bg-orange-50 dark:bg-orange-900/30 rounded-xl lg:rounded-2xl text-orange-500 shrink-0">
                    <flux:icon name="arrow-left-start-on-rectangle" class="size-5 lg:size-7" />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-2xl lg:rounded-[2rem] p-3 lg:p-7 shadow-sm">
            <div class="flex flex-col-reverse lg:flex-row items-center lg:items-start justify-between gap-1 lg:gap-0 text-center lg:text-left">
                <div class="flex-1 min-w-0">
                    <p class="text-zinc-400 text-[9px] lg:text-[13px] font-bold uppercase tracking-wider truncate">Disponibles</p>
                    <h3 class="text-xl lg:text-3xl font-black text-zinc-900 dark:text-white mt-0.5 lg:mt-2">{{ $availableUnits }} <span class="text-[10px] lg:text-lg text-zinc-400">/ {{ count($units) }}</span></h3>
                </div>
                <div class="p-2 lg:p-4 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl lg:rounded-2xl text-emerald-500 shrink-0">
                    <flux:icon name="home" class="size-5 lg:size-7" />
                </div>
            </div>
        </div>
    </div>

    {{-- Accesos Rápidos Táctiles --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
        <button x-data x-on:click="$flux.modal('new-reservation').show()" type="button" class="flex flex-col items-center justify-center gap-2 p-4 sm:p-5 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-2xl shadow-sm hover:shadow-md hover:border-brand-green/30 active:scale-[0.97] transition-all duration-150 min-h-[72px] sm:min-h-[80px]">
            <flux:icon name="plus-circle" class="size-6 sm:size-7 text-[#4a5d41]" />
            <span class="text-[11px] sm:text-xs font-bold text-zinc-700 dark:text-zinc-300 text-center leading-tight">Nueva<br class="sm:hidden"> Reserva</span>
        </button>
        
        <a href="{{ route('inventario') }}" class="flex flex-col items-center justify-center gap-2 p-4 sm:p-5 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-2xl shadow-sm hover:shadow-md hover:border-orange-500/30 active:scale-[0.97] transition-all duration-150 min-h-[72px] sm:min-h-[80px]">
            <flux:icon name="sparkles" class="size-6 sm:size-7 text-orange-500" />
            <span class="text-[11px] sm:text-xs font-bold text-zinc-700 dark:text-zinc-300 text-center leading-tight">Unidades<br class="sm:hidden"> Limpieza</span>
        </a>
        
        <a href="{{ route('reservas') }}" class="flex flex-col items-center justify-center gap-2 p-4 sm:p-5 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-2xl shadow-sm hover:shadow-md hover:border-blue-500/30 active:scale-[0.97] transition-all duration-150 min-h-[72px] sm:min-h-[80px]">
            <flux:icon name="calendar" class="size-6 sm:size-7 text-blue-500" />
            <span class="text-[11px] sm:text-xs font-bold text-zinc-700 dark:text-zinc-300 text-center leading-tight">Ver<br class="sm:hidden"> Calendario</span>
        </a>
        
        <a href="{{ route('registro') }}" class="flex flex-col items-center justify-center gap-2 p-4 sm:p-5 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-2xl shadow-sm hover:shadow-md hover:border-emerald-500/30 active:scale-[0.97] transition-all duration-150 min-h-[72px] sm:min-h-[80px]">
            <flux:icon name="document-text" class="size-6 sm:size-7 text-emerald-500" />
            <span class="text-[11px] sm:text-xs font-bold text-zinc-700 dark:text-zinc-300 text-center leading-tight">Registro<br class="sm:hidden"> Diario</span>
        </a>
    </div>

    {{-- Unidades Pendientes de Limpieza --}}
    @php
        $cleaningUnits = collect($units)->filter(fn($u) => $u->status === 'cleaning');
    @endphp
    @if ($cleaningUnits->isNotEmpty())
        <div class="bg-[#fdfcf7] border border-[#e8e7df] dark:bg-zinc-900/20 dark:border-zinc-800 rounded-[2rem] p-5 sm:p-6 shadow-sm mb-8">
            <div class="flex items-start gap-3 sm:gap-4">
                <div class="p-3 bg-orange-50 dark:bg-orange-900/30 text-orange-500 rounded-2xl shrink-0">
                    <flux:icon name="sparkles" class="size-6" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-zinc-800 dark:text-zinc-300 font-bold text-base sm:text-lg">Unidades pendientes de limpieza</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 text-xs sm:text-sm mt-1">Hay {{ $cleaningUnits->count() }} unidad(es) que requieren servicio de limpieza.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($cleaningUnits as $cu)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-zinc-800 border border-orange-200 dark:border-orange-700 text-orange-700 dark:text-orange-400 text-xs font-bold rounded-xl">
                                {{ $cu->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabs or Sections for Operations --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
        
        {{-- Entradas Pendientes --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl p-5 sm:p-6 shadow-sm">
            <h3 class="text-lg sm:text-xl font-bold text-zinc-900 dark:text-white mb-4 flex items-center gap-2">
                <flux:icon name="arrow-right-start-on-rectangle" class="size-5 text-blue-500" />
                Llegadas Esperadas / Pendientes
            </h3>
            
            <div class="space-y-3 sm:space-y-4 max-h-60 overflow-y-auto desktop-scrollbar pr-1 sm:pr-2">
                @forelse(collect($reservations)->filter(fn($r) => in_array($r->status, ['pending', 'confirmed'])) as $res)
                    <div class="flex items-center justify-between p-3 sm:p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700 gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-zinc-900 dark:text-white text-sm sm:text-base">{{ $res->guest_name }}</p>
                            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 truncate">{{ $res->unit->name ?? 'N/A' }} • Llegada: {{ \Carbon\Carbon::parse($res->check_in)->format('d/m/Y') }}</p>
                            @if($res->status == 'pending')
                                <span class="mt-1 inline-block px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-500">Pendiente de Confirmar</span>
                            @else
                                <span class="mt-1 inline-block px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-500">Confirmado</span>
                            @endif
                        </div>
                        <div class="flex gap-2 shrink-0 items-center">
                            @if($res->status == 'pending')
                                <div class="inline-flex items-stretch rounded-xl shadow-sm overflow-hidden">
                                    <button wire:click="confirmReservation({{ $res->id }})" class="px-4 py-2.5 sm:py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs sm:text-sm font-bold transition-colors border-r border-emerald-600/40">
                                        Confirmar
                                    </button>
                                    <flux:dropdown position="bottom" align="end" class="flex items-stretch">
                                        <button class="px-2 h-full min-h-full bg-emerald-500 hover:bg-emerald-600 text-white transition-colors flex items-center justify-center">
                                            <flux:icon name="chevron-down" class="size-3.5" stroke-width="3" />
                                        </button>
                                        <flux:menu class="w-48 p-1.5 rounded-2xl shadow-xl">
                                            <flux:menu.item @click="$dispatch('open-cancel-modal', { id: {{ $res->id }} })" icon="x-mark" class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl font-bold">
                                                Cancelar Reserva
                                            </flux:menu.item>
                                            <flux:menu.item href="{{ route('ticket.reserva', $res->id) }}" icon="printer" class="text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/20 rounded-xl font-bold">
                                                Generar Ticket PDF
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            @else
                                <div class="inline-flex items-stretch rounded-xl shadow-sm overflow-hidden">
                                    <button wire:click="checkIn({{ $res->id }})" class="px-4 py-2.5 sm:py-2 bg-[#4a5d41] hover:bg-[#3d4d35] text-white text-xs sm:text-sm font-bold transition-colors border-r border-white/20">
                                        Check-In
                                    </button>
                                    <flux:dropdown position="bottom" align="end" class="flex items-stretch">
                                        <button class="px-2 h-full min-h-full bg-[#4a5d41] hover:bg-[#3d4d35] text-white transition-colors flex items-center justify-center">
                                            <flux:icon name="chevron-down" class="size-3.5" stroke-width="3" />
                                        </button>
                                        <flux:menu class="w-48 p-1.5 rounded-2xl shadow-xl">
                                            <flux:menu.item @click="$dispatch('open-cancel-modal', { id: {{ $res->id }} })" icon="x-mark" class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl font-bold">
                                                Cancelar Reserva
                                            </flux:menu.item>
                                            <flux:menu.item href="{{ route('ticket.reserva', $res->id) }}" icon="printer" class="text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/20 rounded-xl font-bold">
                                                Generar Ticket PDF
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-zinc-500 text-sm py-4 text-center">No hay llegadas pendientes hoy.</p>
                @endforelse
            </div>
        </div>

        {{-- Salidas y Activas --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl p-5 sm:p-6 shadow-sm">
            <h3 class="text-lg sm:text-xl font-bold text-zinc-900 dark:text-white mb-4 flex items-center gap-2">
                <flux:icon name="arrow-left-start-on-rectangle" class="size-5 text-orange-500" />
                Huéspedes Activos / Salidas
            </h3>
            
            <div class="space-y-3 sm:space-y-4 max-h-60 overflow-y-auto desktop-scrollbar pr-1 sm:pr-2">
                @forelse(collect($reservations)->filter(fn($r) => $r->status === 'checked_in') as $res)
                    <div class="flex items-center justify-between p-3 sm:p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700 gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-zinc-900 dark:text-white text-sm sm:text-base">{{ $res->guest_name }}</p>
                            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 truncate">{{ $res->unit->name ?? 'N/A' }} • Salida: {{ \Carbon\Carbon::parse($res->check_out)->format('d/m/Y') }}</p>
                        </div>
                        <button wire:click="checkOut({{ $res->id }})" class="px-4 py-2.5 sm:py-2 bg-orange-500 hover:bg-orange-600 text-white text-xs sm:text-sm font-bold rounded-xl transition-colors shadow-sm min-h-[44px] sm:min-h-0 shrink-0">
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

    {{-- Cancel Reason Modal --}}
    <div x-data="{ show: false, id: null, reason: '' }" 
         @open-cancel-modal.window="show = true; id = $event.detail.id; reason = ''"
         x-show="show" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/80 backdrop-blur-sm"
         style="z-index: 60;"
         x-cloak>
        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl w-full max-w-sm overflow-hidden border border-zinc-200 dark:border-zinc-800 p-6" @click.away="show = false">
            <h3 class="text-xl font-black text-red-600 dark:text-red-500 mb-2">Cancelar Reserva</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">¿Estás seguro de que deseas cancelar esta reserva? Por favor, indica el motivo.</p>
            
            <textarea x-model="reason" rows="3" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-3 text-sm focus:ring-red-500 focus:border-red-500 outline-none mb-4 placeholder-zinc-400" placeholder="Motivo de cancelación..."></textarea>
            
            <div class="flex gap-3">
                <button @click="show = false" class="flex-1 py-2.5 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 font-bold rounded-xl transition-all">
                    Atrás
                </button>
                <button @click="$wire.cancelReservation(id, reason); show = false" :disabled="!reason.trim()" class="flex-1 py-2.5 bg-red-600 text-white hover:bg-red-700 font-bold rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>