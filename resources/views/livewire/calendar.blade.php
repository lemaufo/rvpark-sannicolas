<?php

use Livewire\Volt\Component;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public $events = [];
    public $entradasHoy = [];
    public $salidasHoy = [];
    public $estadisticas = [];
    public $diasLlenos = [];
    
    public $allReservations = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $service = app(ReservationService::class);

        $allEvents = $service->getReservationsForCalendar();

        $totalUnits = \App\Models\Unit::count();
        $dailyOccupancy = [];
        
        foreach ($allEvents as $e) {
            // Ignorar las canceladas para calcular la ocupación real
            if ($e['extendedProps']['status'] === 'cancelled') {
                continue;
            }
            $checkIn = Carbon::parse($e['extendedProps']['check_in']);
            $checkOut = Carbon::parse($e['extendedProps']['check_out']);
            
            // Si el check-in es igual al check-out no se cuenta como noche
            if ($checkIn->lt($checkOut)) {
                $period = \Carbon\CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
                foreach ($period as $date) {
                    $dateStr = $date->toDateString();
                    $dailyOccupancy[$dateStr] = ($dailyOccupancy[$dateStr] ?? 0) + 1;
                }
            }
        }
        
        foreach ($dailyOccupancy as $dateStr => $count) {
            if ($totalUnits > 0 && $count >= $totalUnits) {
                $this->diasLlenos[] = $dateStr;
            }
        }

        // Mostrar reservaciones (incluyendo pendientes) en el calendario visual
        $this->events = collect($allEvents)->filter(function($e) {
            $status = $e['extendedProps']['status'];
            return in_array($status, ['pending', 'confirmed', 'checked_in', 'checked_out']);
        })->values()->toArray();

        $today = Carbon::today()->toDateString();
        
        // Filter "Entradas Hoy" (check-in matches today)
        $this->entradasHoy = collect($this->events)
            ->filter(fn($e) => $e['start'] === $today)
            ->map(fn($e) => [
                'guest' => $e['extendedProps']['guest_name'],
                'unit' => $e['extendedProps']['unit_name'],
                'type' => $e['extendedProps']['unit_type'],
            ])->toArray();

        // Filter "Salidas Hoy" (check_out/end matches today)
        $this->salidasHoy = collect($this->events)
            ->filter(fn($e) => $e['extendedProps']['check_out'] === $today)
            ->map(fn($e) => [
                'guest' => $e['extendedProps']['guest_name'],
                'unit' => $e['extendedProps']['unit_name'],
                'type' => $e['extendedProps']['unit_type'],
            ])->toArray();

        // Stats calculation based on real events
        $total = count($this->events);
        
        // As there is no origin field in the database yet, we set these to 0
        $directas = 0;
        $telefono = 0;
        $web = 0;

        $this->estadisticas = [
            'total' => $total,
            'directas' => $directas,
            'telefono' => $telefono,
            'web' => $web,
        ];
        
        // Load data for the List View
        $role = auth()->user() ? auth()->user()->role : 'receptionist';
        $reservationsForList = \App\Models\Reservation::with('unit')->orderBy('check_in', 'desc')->get();
        
        if ($role !== 'admin') {
            $reservationsForList = $reservationsForList->filter(function($r) use ($today) {
                return $r->check_in >= $today || in_array($r->status, ['checked_in']);
            });
        }
        
        $this->allReservations = $reservationsForList->map(function($r) {
            return [
                'id' => $r->id,
                'guest_name' => $r->guest_name,
                'guest_phone' => $r->guest_phone,
                'unit_name' => $r->unit ? $r->unit->name : 'N/A',
                'unit_type' => $r->unit ? strtolower($r->unit->type) : 'bungalow',
                'check_in' => $r->check_in,
                'check_in_time' => $r->check_in_time,
                'check_out' => $r->check_out,
                'check_out_time' => $r->check_out_time,
                'status' => $r->status,
                'total_amount' => (float)$r->total_amount,
                'created_at' => $r->created_at ? $r->created_at->format('Y-m-d H:i') : null,
                'updated_at' => $r->updated_at ? $r->updated_at->format('Y-m-d H:i') : null,
            ];
        })->values()->toArray();
    }

    public function cancelReservation($id, $reason)
    {
        try {
            $reservation = \App\Models\Reservation::find($id);
            if ($reservation && $reservation->status !== 'cancelled') {
                $service = app(ReservationService::class);
                $service->cancelReservation($reservation, auth()->id(), $reason);
                Log::info("Reserva cancelada: {$id}. Motivo: {$reason}. Usuario: " . (auth()->user()->name ?? 'N/A'));
                $this->loadData();
                $this->dispatch('reservation-cancelled', ['events' => $this->events, 'allReservations' => $this->allReservations]);
                $this->dispatch('swal-success', ['title' => 'Reserva cancelada', 'message' => 'La reserva se ha cancelado exitosamente.']);
            } else {
                $this->dispatch('swal-error', 'No se pudo cancelar la reserva. Es posible que ya esté cancelada.');
            }
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo cancelar la reserva. Intenta de nuevo.');
        }
    }
};
?>

<div x-data="calendarApp({ 
    events: @js($events),
    diasLlenos: @js($diasLlenos),
    allReservations: @js($allReservations),
    initialDate: '{{ \Carbon\Carbon::today()->toDateString() }}'
})" 
@reservation-cancelled.window="events = $event.detail[0].events; allReservations = $event.detail[0].allReservations; initCalendar(); showModal = false; showCancelModal = false; filterReservations();"
class="space-y-8">
    {{-- Scripts for FullCalendar --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    {{-- Top Action Bar / Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-8">
        <div>
            <h1 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight">Reservaciones</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-1">Administrar reservas y disponibilidad</p>
        </div>

        {{-- Switch View Mode --}}
        <div class="flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800 p-1 rounded-2xl border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm">
            <button @click="viewMode = 'calendar'" 
                :class="viewMode === 'calendar' ? 'bg-[#4a5d41] text-white shadow-md' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'" 
                class="px-5 py-2 text-sm font-bold rounded-xl transition-all duration-200">
                Calendario
            </button>
            <button @click="viewMode = 'list'"
                :class="viewMode === 'list' ? 'bg-[#4a5d41] text-white shadow-md' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'" 
                class="px-5 py-2 text-sm font-bold rounded-xl transition-all duration-200">
                Lista
            </button>
        </div>
    </div>

    {{-- Top Cards Section (Orange area in user description) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Entradas Hoy Card --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-[2rem] p-6 shadow-sm flex flex-col min-h-[190px]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-zinc-800 dark:text-zinc-200 font-bold text-lg">Entradas Hoy</h3>
                <span class="bg-emerald-600 text-white rounded-full size-6 flex items-center justify-center text-xs font-bold">
                    {{ count($entradasHoy) }}
                </span>
            </div>
            <div class="space-y-3 overflow-y-auto flex-1 max-h-[150px] pr-1">
                @forelse($entradasHoy as $item)
                    <div class="bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-2xl flex flex-col gap-0.5 border border-zinc-100 dark:border-zinc-800/80">
                        <span class="font-bold text-zinc-800 dark:text-zinc-200 text-sm">{{ $item['guest'] }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400 text-xs">{{ $item['unit'] }}</span>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-zinc-400 dark:text-zinc-500 py-4">
                        <span class="text-sm font-medium">No hay entradas hoy</span>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Salidas Hoy Card --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-[2rem] p-6 shadow-sm flex flex-col min-h-[190px]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-zinc-800 dark:text-zinc-200 font-bold text-lg">Salidas Hoy</h3>
                <span class="bg-[#b7791f] text-white rounded-full size-6 flex items-center justify-center text-xs font-bold">
                    {{ count($salidasHoy) }}
                </span>
            </div>
            <div class="space-y-3 overflow-y-auto flex-1 max-h-[150px] pr-1">
                @forelse($salidasHoy as $item)
                    <div class="bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-2xl flex flex-col gap-0.5 border border-zinc-100 dark:border-zinc-800/80">
                        <span class="font-bold text-zinc-800 dark:text-zinc-200 text-sm">{{ $item['guest'] }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400 text-xs">{{ $item['unit'] }}</span>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full text-zinc-400 dark:text-zinc-500 py-4">
                        <span class="text-sm font-medium">No hay salidas hoy</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Main View Container (Celeste area in user description) --}}
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-[2rem] p-6 sm:p-8 shadow-sm">
        
        {{-- Calendar View Wrapper --}}
        <div :class="viewMode === 'calendar' ? '' : 'hidden'" class="space-y-6">
            
            {{-- Custom Calendar Navigation Bar --}}
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-4 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6">
                    <h2 class="text-2xl font-extrabold text-zinc-900 dark:text-white capitalize tracking-tight" x-text="currentTitle"></h2>
                    
                    {{-- Color Legends --}}
                    <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                        <span class="flex items-center gap-1.5">
                            <span class="size-3 rounded-full bg-[#4a5d41]"></span>
                            Bungalows
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="size-3 rounded-full bg-[#0d9488]"></span>
                            RV Spots
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="size-3 rounded-full bg-[#d97706]"></span>
                            Camping
                        </span>
                    </div>
                </div>
                
                <div class="flex items-center gap-4 self-end sm:self-center">
                    {{-- Mes / Semana selector --}}
                    <div class="flex items-center bg-zinc-100 dark:bg-zinc-800 p-0.5 rounded-xl border border-zinc-200/50 dark:border-zinc-700/50">
                        <button @click="setView('dayGridMonth')" 
                            :class="currentView === 'dayGridMonth' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'" 
                            class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all duration-150">
                            Mes
                        </button>
                        <button @click="setView('dayGridWeek')" 
                            :class="currentView === 'dayGridWeek' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'" 
                            class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all duration-150">
                            Semana
                        </button>
                    </div>

                    {{-- Next / Prev Buttons --}}
                    <div class="flex items-center gap-1.5">
                        <button @click="prev()" class="p-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 active:scale-95 rounded-xl transition-all text-zinc-700 dark:text-zinc-300">
                            <flux:icon name="chevron-left" class="size-4" />
                        </button>
                        <button @click="next()" class="p-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 active:scale-95 rounded-xl transition-all text-zinc-700 dark:text-zinc-300">
                            <flux:icon name="chevron-right" class="size-4" />
                        </button>
                    </div>
                </div>
            </div>

            {{-- FullCalendar container --}}
            <div class="w-full overflow-x-auto">
                <div class="min-w-[650px] lg:min-w-full">
                    <div id="calendar-el" wire:ignore class="w-full"></div>
                </div>
            </div>


        </div>

        {{-- List View Wrapper --}}
        <div x-show="viewMode === 'list'" class="space-y-6" x-cloak>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-100 dark:border-zinc-800">
                <h2 class="text-2xl font-extrabold text-zinc-900 dark:text-white capitalize tracking-tight">Todas las Reservaciones</h2>
                
                {{-- Filters --}}
                <div class="flex flex-wrap items-center gap-2">
                    <input type="date" x-model="filters.date" @change="filterReservations()" class="bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-sm rounded-xl px-3 py-1.5 focus:ring-[#4a5d41] outline-none" placeholder="Fecha">
                    <select x-model="filters.unit" @change="filterReservations()" class="bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-sm rounded-xl px-3 py-1.5 focus:ring-[#4a5d41] outline-none">
                        <option value="">Todas las Unidades</option>
                        <option value="bungalow">Bungalows</option>
                        <option value="rv">RV Spots</option>
                        <option value="camping">Camping</option>
                    </select>
                    <select x-model="filters.status" @change="filterReservations()" class="bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-sm rounded-xl px-3 py-1.5 focus:ring-[#4a5d41] outline-none">
                        <option value="">Todos los Estados</option>
                        <option value="pending">Pendientes</option>
                        <option value="confirmed">Confirmadas</option>
                        <option value="checked_in">Activas</option>
                        <option value="checked_out">Completadas</option>
                        <option value="cancelled">Canceladas</option>
                    </select>
                </div>
            </div>

            <div class="space-y-4">
                <template x-for="res in filteredReservations" :key="res.id">
                    <div class="bg-white dark:bg-zinc-800/50 p-4 sm:p-6 rounded-[1.5rem] border border-zinc-200 dark:border-zinc-700/50 shadow-sm flex flex-col sm:flex-row gap-4 sm:items-center justify-between transition-all hover:border-[#4a5d41]/30">
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-zinc-900 dark:text-white text-lg" x-text="res.guest_name"></h3>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full text-white"
                                    :class="res.status === 'confirmed' ? 'bg-emerald-500' : (res.status === 'checked_in' ? 'bg-[#4a5d41]' : (res.status === 'checked_out' ? 'bg-zinc-500' : (res.status === 'cancelled' ? 'bg-red-500' : 'bg-amber-500')))"
                                    x-text="res.status === 'confirmed' ? 'Registrado' : (res.status === 'checked_in' ? 'Activo' : (res.status === 'checked_out' ? 'Completado' : (res.status === 'cancelled' ? 'Cancelado' : 'Pendiente')))">
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-zinc-500 dark:text-zinc-400 font-medium">
                                <div class="flex items-center gap-1">
                                    <flux:icon name="map-pin" class="size-4" />
                                    <span x-text="res.unit_name"></span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <flux:icon name="calendar" class="size-4" />
                                    <span x-text="res.check_in + ' - ' + res.check_out"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between sm:justify-end gap-6 sm:w-1/3">
                            <div class="text-right">
                                <span class="font-black text-[#4a5d41] dark:text-emerald-400 text-lg" x-text="'$' + res.total_amount"></span>
                            </div>
                            <button @click="openListDetail(res)" class="px-4 py-2 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 font-bold rounded-xl transition-all text-sm">
                                Detalles
                            </button>
                        </div>
                    </div>
                </template>
                <div x-show="filteredReservations.length === 0" class="text-center py-10 text-zinc-500 font-medium">
                    No se encontraron reservaciones con los filtros aplicados.
                </div>
            </div>
        </div>

    </div>

    {{-- Detail Modal Popup (Quick details on click) --}}
    <div x-show="showModal" 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/60 backdrop-blur-sm"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak>
        
        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden border border-zinc-200 dark:border-zinc-800"
            @click.away="showModal = false"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            
            {{-- Modal Header --}}
            <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800 flex justify-between items-center bg-zinc-50 dark:bg-zinc-900/40">
                <h3 class="text-lg font-black text-zinc-900 dark:text-white flex items-center gap-2">
                    <flux:icon name="information-circle" class="size-5 text-[#4a5d41]" />
                    Detalle de Reserva
                </h3>
                <button @click="showModal = false" class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-200/50 dark:hover:bg-zinc-800 rounded-full transition-colors">
                    <flux:icon name="x-mark" class="size-5" />
                </button>
            </div>

            {{-- Modal Content --}}
            <template x-if="selectedEvent">
                <div class="p-5 space-y-4">
                    {{-- Guest Info --}}
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-2xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-600 dark:text-zinc-400 shrink-0">
                            <flux:icon name="user" class="size-5" />
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-zinc-900 dark:text-white" x-text="selectedEvent.guest_name"></h4>
                            <p class="text-zinc-500 dark:text-zinc-400 text-sm mt-0.5" x-text="selectedEvent.guest_phone"></p>
                        </div>
                    </div>

                    {{-- Unit Info --}}
                    <div class="grid grid-cols-2 gap-3 bg-zinc-50 dark:bg-zinc-800/40 p-3 rounded-2xl border border-zinc-100 dark:border-zinc-800/80">
                        <div>
                            <span class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider">Unidad</span>
                            <span class="block font-bold text-zinc-800 dark:text-zinc-200 mt-1" x-text="selectedEvent.unit_name"></span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider">Tipo</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold capitalize mt-1.5"
                                :class="selectedEvent.unit_type === 'bungalow' ? 'bg-purple-100/70 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : (selectedEvent.unit_type === 'rv' ? 'bg-teal-100/70 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400' : 'bg-amber-100/70 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400')"
                                x-text="selectedEvent.unit_type">
                            </span>
                        </div>
                    </div>

                    {{-- Dates --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider">Check-in</span>
                            <div class="flex items-center gap-2 mt-1.5 text-zinc-700 dark:text-zinc-300">
                                <flux:icon name="calendar-days" class="size-4 text-zinc-400" />
                                <span class="font-bold text-sm" x-text="selectedEvent.check_in"></span>
                            </div>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider">Check-out</span>
                            <div class="flex items-center gap-2 mt-1.5 text-zinc-700 dark:text-zinc-300">
                                <flux:icon name="calendar-days" class="size-4 text-zinc-400" />
                                <span class="font-bold text-sm" x-text="selectedEvent.check_out"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Status & Amount --}}
                    <div class="flex items-center justify-between p-3 bg-[#4a5d41]/5 dark:bg-[#4a5d41]/10 rounded-2xl border border-[#4a5d41]/10">
                        <div>
                            <span class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider">Total Estimado</span>
                            <span class="block text-2xl font-black text-[#4a5d41] dark:text-emerald-400 mt-0.5" 
                                x-text="'$' + Number(selectedEvent.total_amount).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})">
                            </span>
                        </div>
                        
                        <div class="text-right">
                            <span class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-1.5">Estado</span>
                            <span class="px-2.5 py-1 text-xs font-bold rounded-xl border"
                                :class="selectedEvent.status === 'confirmed' ? 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/20' : (selectedEvent.status === 'checked_in' ? 'bg-[#4a5d41]/10 text-[#4a5d41] border-[#4a5d41]/20 dark:bg-[#4a5d41]/30 dark:text-[#a3b899] dark:border-[#4a5d41]/40' : (selectedEvent.status === 'checked_out' ? 'bg-zinc-100 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700' : (selectedEvent.status === 'cancelled' ? 'bg-red-50 text-red-700 border-red-100 dark:bg-red-950/30 dark:text-red-400 dark:border-red-900/20' : 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/20')))"
                                x-text="selectedEvent.status === 'confirmed' ? 'Confirmada' : (selectedEvent.status === 'checked_in' ? 'Activa' : (selectedEvent.status === 'checked_out' ? 'Completada' : (selectedEvent.status === 'cancelled' ? 'Cancelada' : 'Pendiente')))">
                            </span>
                        </div>
                    </div>
                    
                    {{-- Timeline --}}
                    <div x-show="selectedEvent.created_at" class="bg-zinc-50 dark:bg-zinc-800/40 p-3 rounded-2xl border border-zinc-100 dark:border-zinc-800/80">
                        <span class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-2">Línea de tiempo</span>
                        <div class="space-y-2 relative before:absolute before:inset-0 before:ml-2.5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-zinc-300 dark:before:via-zinc-700 before:to-transparent">
                            <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                                <div class="flex items-center justify-center size-5 rounded-full border border-white dark:border-zinc-900 bg-zinc-200 dark:bg-zinc-700 text-zinc-500 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2"></div>
                                <div class="w-[calc(100%-2rem)] md:w-[calc(50%-1.5rem)] pl-3 md:pl-0 md:group-even:pr-3 md:group-odd:pl-3">
                                    <div class="flex flex-col">
                                        <div class="text-sm font-bold text-zinc-800 dark:text-zinc-200">Reserva Creada</div>
                                        <time class="text-xs text-zinc-500 dark:text-zinc-400" x-text="selectedEvent.created_at"></time>
                                    </div>
                                </div>
                            </div>
                            
                            <template x-if="selectedEvent.status === 'cancelled'">
                                <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                                    <div class="flex items-center justify-center size-5 rounded-full border border-white dark:border-zinc-900 bg-red-500 text-white shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2"></div>
                                    <div class="w-[calc(100%-2rem)] md:w-[calc(50%-1.5rem)] pl-3 md:pl-0 md:group-even:pr-3 md:group-odd:pl-3">
                                        <div class="flex flex-col">
                                            <div class="text-sm font-bold text-red-600 dark:text-red-400">Reserva Cancelada</div>
                                            <time class="text-xs text-zinc-500 dark:text-zinc-400" x-text="selectedEvent.updated_at"></time>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <template x-if="selectedEvent.status === 'checked_in' || selectedEvent.status === 'checked_out'">
                                <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                                    <div class="flex items-center justify-center size-5 rounded-full border border-white dark:border-zinc-900 bg-blue-500 text-white shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2"></div>
                                    <div class="w-[calc(100%-2rem)] md:w-[calc(50%-1.5rem)] pl-3 md:pl-0 md:group-even:pr-3 md:group-odd:pl-3">
                                        <div class="flex flex-col">
                                            <div class="text-sm font-bold text-blue-600 dark:text-blue-400">Check-in Realizado</div>
                                            <time class="text-xs text-zinc-500 dark:text-zinc-400" x-text="selectedEvent.check_in_time || selectedEvent.updated_at"></time>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-col gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <button @click="downloadTicket(selectedEvent.id.replace('real_', ''))"
                            class="w-full py-2 bg-[#4a5d41] text-white hover:bg-[#3d4d35] font-bold rounded-xl transition-all shadow-sm active:scale-[0.98] flex items-center justify-center gap-2">
                            <flux:icon name="printer" class="size-4" />
                            Generar Ticket PDF
                        </button>
                        <button @click="showModal = false" 
                            class="w-full py-2 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 font-bold rounded-xl transition-all shadow-sm active:scale-[0.98]">
                            Cerrar Detalles
                        </button>
                        
                        <template x-if="selectedEvent.status !== 'cancelled' && selectedEvent.status !== 'checked_out'">
                            <button @click="openCancelModal()" 
                                class="w-full py-2 bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950/30 dark:text-red-400 dark:hover:bg-red-900/40 border border-red-200 dark:border-red-900/30 font-bold rounded-xl transition-all shadow-sm active:scale-[0.98]">
                                Cancelar Reserva
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
    
    {{-- Cancel Reason Modal --}}
    <div x-show="showCancelModal" 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/80 backdrop-blur-sm"
        style="z-index: 60;"
        x-cloak>
        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl w-full max-w-sm overflow-hidden border border-zinc-200 dark:border-zinc-800 p-6">
            <h3 class="text-xl font-black text-red-600 dark:text-red-500 mb-2">Cancelar Reserva</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">¿Estás seguro de que deseas cancelar esta reserva? Por favor, indica el motivo.</p>
            
            <textarea x-model="cancelReason" rows="3" class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-3 text-sm focus:ring-red-500 focus:border-red-500 outline-none mb-4 placeholder-zinc-400" placeholder="Motivo de cancelación..."></textarea>
            
            <div class="flex gap-3">
                <button @click="showCancelModal = false; cancelReason = ''" class="flex-1 py-2.5 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 font-bold rounded-xl transition-all">
                    Atrás
                </button>
                <button @click="submitCancel()" :disabled="!cancelReason.trim()" class="flex-1 py-2.5 bg-red-600 text-white hover:bg-red-700 font-bold rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    {{-- Day Detail Modal --}}
    <div x-show="showDayModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/60 backdrop-blur-sm"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak>

        <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl w-full max-w-sm overflow-hidden flex flex-col border border-zinc-200 dark:border-zinc-800"
            style="max-height: 90vh;"
            @click.away="showDayModal = false"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">

            {{-- Header --}}
            <div class="px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 flex justify-between items-center bg-zinc-50 dark:bg-zinc-900/40 shrink-0">
                <h3 class="text-lg font-black text-zinc-900 dark:text-white capitalize"
                    x-text="selectedDayData ? selectedDayData.dateFormatted : ''"></h3>
                <button @click="showDayModal = false"
                    class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-200/50 dark:hover:bg-zinc-800 rounded-full transition-colors">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <template x-if="selectedDayData">
                <div class="p-5 space-y-4 overflow-y-auto flex-1">

                    {{-- Seccion: Entradas de hoy / esperadas / pasadas --}}
                    <div>
                        <div class="flex items-center gap-2 mb-2.5">
                            <span class="size-2 rounded-full bg-emerald-500 shrink-0"></span>
                            <span class="text-xs font-black text-zinc-500 dark:text-zinc-400 uppercase tracking-widest"
                                x-text="selectedDayData.entradasLabel"></span>
                            <span class="ml-auto text-xs font-bold text-emerald-600 dark:text-emerald-400"
                                x-show="selectedDayData.entradas.length > 0"
                                x-text="selectedDayData.entradas.length"></span>
                        </div>

                        <template x-if="selectedDayData.entradas.length === 0">
                            <p class="text-sm text-zinc-400 dark:text-zinc-500 italic text-center py-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-2xl">Sin entradas</p>
                        </template>

                        <template x-if="selectedDayData.entradas.length > 0">
                            {{-- max-height = 3 items × ~46px; scrollbar si hay mas --}}
                            <div class="space-y-1.5 overflow-y-auto pr-0.5" style="max-height: 138px;">
                                <template x-for="(item, i) in selectedDayData.entradas" :key="i">
                                    <button
                                        @click="openEntradaDetail(item)"
                                        class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-2xl border text-left cursor-pointer hover:brightness-95 transition-all active:scale-[0.98]"
                                        :style="item.unit_type === 'bungalow'
                                            ? 'background:#f0f4ee; border-color:#c8d9c4;'
                                            : (item.unit_type === 'rv'
                                                ? 'background:#e6f7f5; border-color:#9dd6d0;'
                                                : 'background:#fef3e2; border-color:#f9d5a0;')">
                                        <div class="size-6 rounded-full flex items-center justify-center shrink-0"
                                            :style="item.unit_type === 'bungalow'
                                                ? 'background:#4a5d41'
                                                : (item.unit_type === 'rv'
                                                    ? 'background:#0d9488'
                                                    : 'background:#d97706')">
                                            <svg class="size-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        </div>
                                        <span class="font-semibold text-zinc-800 dark:text-zinc-200 text-sm truncate flex-1" x-text="item.name"></span>
                                        {{-- Chevron indicador de clic --}}
                                        <svg class="size-3.5 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Divider --}}
                    <div class="w-full h-px bg-zinc-100 dark:bg-zinc-800"></div>

                    {{-- Seccion: Estancias vigentes --}}
                    <div>
                        <div class="flex items-center gap-2 mb-2.5">
                            <span class="size-2 rounded-full bg-[#4a5d41] shrink-0"></span>
                            <span class="text-xs font-black text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">Estancias vigentes</span>
                            <span class="ml-auto text-xs font-bold text-[#4a5d41] dark:text-emerald-400"
                                x-show="selectedDayData.estancias.length > 0"
                                x-text="selectedDayData.estancias.length"></span>
                        </div>

                        <template x-if="selectedDayData.estancias.length === 0">
                            <p class="text-sm text-zinc-400 dark:text-zinc-500 italic text-center py-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-2xl">Sin estancias activas</p>
                        </template>

                        <template x-if="selectedDayData.estancias.length > 0">
                            {{-- max-height = 3 items × ~46px = 138px; scrollbar si hay mas de 3 --}}
                            <div class="overflow-y-auto" style="max-height: 138px;">
                                <div class="space-y-1">
                                    <template x-for="(item, i) in selectedDayData.estancias" :key="i">
                                        <button
                                            @click="openEntradaDetail(item)"
                                            class="w-full flex items-center gap-2 px-3 py-2.5 rounded-2xl transition-all cursor-pointer hover:brightness-95 active:scale-[0.98] text-left"
                                            :style="item.unit_type === 'bungalow'
                                                ? 'background:#f0f4ee;'
                                                : (item.unit_type === 'rv'
                                                    ? 'background:#e6f7f5;'
                                                    : 'background:#fef3e2;')">
                                            {{-- Punto de color del tipo de unidad --}}
                                            <span class="size-2 rounded-full shrink-0"
                                                :style="item.unit_type === 'bungalow'
                                                    ? 'background:#4a5d41'
                                                    : (item.unit_type === 'rv'
                                                        ? 'background:#0d9488'
                                                        : 'background:#d97706')"></span>
                                            {{-- Nombre truncado --}}
                                            <span
                                                class="font-semibold text-zinc-800 dark:text-zinc-200 text-sm truncate flex-1"
                                                x-text="item.name">
                                            </span>
                                            {{-- Rango de fechas entrada - salida --}}
                                            <span
                                                class="shrink-0 text-xs font-bold text-zinc-500 tabular-nums"
                                                x-text="item.checkinFormatted + ' - ' + item.checkoutFormatted">
                                            </span>
                                            {{-- Chevron indicador de clic --}}
                                            <svg class="size-3.5 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <template x-if="selectedDayData.anteriores && selectedDayData.anteriores.length > 0">
                        <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800/80">
                            <div class="flex justify-end">
                                <button @click="selectedDayData.showAnteriores = !selectedDayData.showAnteriores" 
                                    class="text-[10px] font-bold text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors uppercase tracking-widest flex items-center gap-1">
                                    <span x-text="selectedDayData.showAnteriores ? 'Ocultar anteriores' : 'Ver anteriores'"></span>
                                    <svg class="size-3 transition-transform" :class="selectedDayData.showAnteriores ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                            </div>
                            
                            <div x-show="selectedDayData.showAnteriores" x-collapse class="mt-3 space-y-1.5 overflow-y-auto pr-0.5" style="max-height: 138px;">
                                <template x-for="(item, i) in selectedDayData.anteriores" :key="i">
                                    <button
                                        @click="openEntradaDetail(item)"
                                        class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-2xl border text-left cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all active:scale-[0.98] border-zinc-200 dark:border-zinc-700/50 bg-zinc-50 dark:bg-zinc-800/20 opacity-70">
                                        <span class="size-2 rounded-full shrink-0 bg-zinc-400"></span>
                                        <span class="font-semibold text-zinc-500 dark:text-zinc-400 text-sm truncate flex-1 line-through decoration-zinc-300 dark:decoration-zinc-600" x-text="item.name"></span>
                                        <span class="shrink-0 text-xs font-bold text-zinc-400 tabular-nums" x-text="item.checkinFormatted + ' - ' + item.checkoutFormatted"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                </div>
            </template>
        </div>
    </div>

    {{-- Styling overrides for FullCalendar --}}
    <style>
        /* Modern light/dark border variables */
        .fc {
            --fc-border-color: #f4f4f5;
            --fc-today-bg-color: #f7fee7;
            font-family: inherit;
        }
        .dark .fc {
            --fc-border-color: #27272a;
            --fc-today-bg-color: rgba(132, 204, 22, 0.08);
            --fc-page-bg-color: #18181b;
        }

        /* Border tweaks */
        .fc-theme-standard td, .fc-theme-standard th {
            border: 1px solid #f4f4f5 !important;
        }
        .dark .fc-theme-standard td, .dark .fc-theme-standard th {
            border: 1px solid #27272a !important;
        }
        
        .fc .fc-scrollgrid {
            border-radius: 1.5rem !important;
            overflow: hidden !important;
            border: 1px solid #f4f4f5 !important;
        }
        .dark .fc .fc-scrollgrid {
            border: 1px solid #27272a !important;
        }

        /* Day names header styling */
        .fc-col-header-cell {
            background-color: #fafafa !important;
            padding: 12px 8px !important;
            font-size: 0.85rem !important;
            font-weight: 700 !important;
            color: #71717a !important;
            text-transform: capitalize;
        }
        .dark .fc-col-header-cell {
            background-color: #1c1c1f !important;
            color: #a1a1aa !important;
        }

        /* Day grid number positioning */
        .fc .fc-daygrid-day-number {
            font-size: 0.85rem !important;
            font-weight: 700 !important;
            color: #71717a !important;
            padding: 10px !important;
            display: inline-block;
        }
        .dark .fc .fc-daygrid-day-number {
            color: #a1a1aa !important;
        }

        /* Today Day Number Highlight */
        .fc .fc-day-today {
            background-color: #f7fee7 !important;
        }
        .dark .fc .fc-day-today {
            background-color: rgba(132, 204, 22, 0.08) !important;
        }

        .fc .fc-day-today .fc-daygrid-day-number {
            color: #84cc16 !important;
            font-weight: 800 !important;
        }

        /* =========================================
           FULLCALENDAR × TAILWIND CSS — CONFLICT FIX
           =========================================
           Tailwind's preflight resets table-layout to 'auto' and
           removes min-width from table cells. This causes FullCalendar's
           multi-day event harnesses to overflow their column boundaries.
           The fix has 3 layers:
           1. Force fixed table layout so column widths are respected.
           2. Prevent .fc-daygrid-day-frame from growing beyond its cell.
           3. Force .fc-daygrid-event-harness (the REAL event wrapper) to
              clip itself to 100% of its column, hiding the overflow.
        */
        .fc table {
            table-layout: fixed !important;
            width: 100% !important;
            min-width: 0 !important;
        }
        .fc th, .fc td {
            min-width: 0 !important;
        }

        /* Event pill styling */
        .fc-event, .fc-h-event {
            margin-top: 2px !important;
            margin-bottom: 2px !important;
            margin-left: 2px !important;
            margin-right: 2px !important;
            transition: all 0.2s ease !important;
            border-radius: 16px !important;
            overflow: hidden !important;
        }

        .fc-event-main {
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            padding: 4px 8px !important;
        }
        .fc-event:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            filter: brightness(0.95) !important;
        }

        /* Max event grouping text */
        .fc .fc-daygrid-more-link {
            font-size: 0.75rem !important;
            font-weight: 800 !important;
            color: #4a5d41 !important;
            padding: 3px 6px !important;
            background-color: #f4f5f2 !important;
            border-radius: 6px !important;
            display: inline-block;
            margin-top: 2px !important;
            transition: all 0.15s ease;
        }
        .dark .fc .fc-daygrid-more-link {
            color: #a3e635 !important;
            background-color: #272c22 !important;
        }
        .fc .fc-daygrid-more-link:hover {
            background-color: #e4e7de !important;
            text-decoration: none !important;
        }
        .dark .fc .fc-daygrid-more-link:hover {
            background-color: #373e30 !important;
        }
        
        /* Suprimir completamente el popover nativo de FullCalendar.
           Usamos nuestro propio modal personalizado en su lugar. */
        .fc-popover {
            display: none !important;
        }
        
        /* Hide time elements in events */
        .fc-event-time {
            display: none !important;
        }
        
        /* Disable focus rings in FullCalendar */
        .fc * {
            outline: none !important;
            box-shadow: none !important;
        }
    </style>

    <script>
        function calendarApp(config) {
            let calendarInstance = null;
            return {
                events: config.events,
                diasLlenos: config.diasLlenos || [],
                allReservations: config.allReservations || [],
                filteredReservations: [],
                filters: {
                    date: '',
                    unit: '',
                    status: ''
                },
                viewMode: 'calendar',
                currentTitle: '',
                currentView: 'dayGridMonth',
                selectedEvent: null,
                showModal: false,
                showDayModal: false,
                selectedDayData: null,
                
                showCancelModal: false,
                cancelReason: '',
                cancelReservationId: null,

                init() {
                    // Wait for FullCalendar to be loaded if navigating via wire:navigate
                    const checkAndInit = () => {
                        if (typeof FullCalendar !== 'undefined') {
                            this.initCalendar();
                        } else {
                            setTimeout(checkAndInit, 50);
                        }
                    };
                    
                    this.$nextTick(() => {
                        checkAndInit();
                        this.filterReservations();
                    });
                },
                
                filterReservations() {
                    this.filteredReservations = this.allReservations.filter(res => {
                        let match = true;
                        if (this.filters.date) {
                            if (res.check_in !== this.filters.date) match = false;
                        }
                        if (this.filters.unit) {
                            if (res.unit_type !== this.filters.unit) match = false;
                        }
                        if (this.filters.status) {
                            if (res.status !== this.filters.status) match = false;
                        }
                        return match;
                    });
                },
                
                openListDetail(res) {
                    this.selectedEvent = res;
                    this.showModal = true;
                },
                
                openCancelModal() {
                    this.cancelReason = '';
                    // El ID puede venir del calendario (ej. "real_5") o de la lista (ej. 5)
                    this.cancelReservationId = String(this.selectedEvent.id).replace('real_', '');
                    this.showCancelModal = true;
                },
                
                submitCancel() {
                    if (!this.cancelReason.trim()) {
                        showSwalErrorToast('Campo obligatorio', 'Por favor ingresa el motivo de cancelación.');
                        return;
                    }
                    Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).call('cancelReservation', this.cancelReservationId, this.cancelReason);
                },

                async downloadTicket(id) {
                    const url = '{{ url("/ticket/reserva") }}/' + id;
                    try {
                        const resp = await fetch(url);
                        const blob = await resp.blob();
                        const disposition = resp.headers.get('content-disposition') || '';
                        const match = disposition.match(/filename="?([^";\n]+)"?/);
                        const filename = match ? match[1] : 'ticket.pdf';
                        const a = document.createElement('a');
                        a.href = URL.createObjectURL(blob);
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(a.href);
                    } catch (e) {
                        console.error('Error downloading ticket:', e);
                        showSwalErrorToast('Error', 'No se pudo descargar el ticket. Intenta de nuevo.');
                    }
                },

                initCalendar() {
                    const calendarEl = document.getElementById('calendar-el');
                    if (!calendarEl) return;

                    calendarInstance = new FullCalendar.Calendar(calendarEl, {
                        initialView: this.currentView,
                        initialDate: config.initialDate,
                        locale: 'es',
                        firstDay: 0, // Domingo
                        headerToolbar: false, // Custom header handled via Alpine
                        views: {
                            dayGridMonth: {
                                dayMaxEvents: 2
                            },
                            dayGridWeek: {
                                dayMaxEvents: false
                            }
                        },
                        moreLinkText: 'Más', // Override default "more" text
                        eventDisplay: 'block', // Force events to act as blocks and respect boundaries
                        events: this.events.filter(e => e.extendedProps && e.extendedProps.status !== 'checked_out'),
                        eventOrder: 'end,duration,title', // Custom order: Earliest checkout first, then shortest stay
                        editable: false,
                        selectable: false,
                        height: 'auto',
                        eventClick: (info) => {
                            // Close the FullCalendar "+more" popover if it's open,
                            // to prevent it from overlapping the custom detail modal.
                            const popover = document.querySelector('.fc-more-popover');
                            if (popover) {
                                popover.style.display = 'none';
                            }
                            this.openEventDetails(info.event);
                        },
                        dayCellDidMount: (info) => {
                            const llenos = config.diasLlenos || [];
                            if (llenos.includes(info.dateStr)) {
                                info.el.style.backgroundColor = '#fee2e2'; // Pale red (baito)
                                
                                const dayTop = info.el.querySelector('.fc-daygrid-day-top');
                                if (dayTop) {
                                    dayTop.style.display = 'flex';
                                    dayTop.style.flexDirection = 'column';
                                    
                                    const badge = document.createElement('div');
                                    badge.className = 'text-[9.5px] text-red-600/90 font-black uppercase mt-0.5 w-full text-center tracking-wider leading-none';
                                    badge.innerText = 'Cupo lleno';
                                    dayTop.appendChild(badge);
                                }
                            }
                        },
                        datesSet: (dateInfo) => {
                            this.currentTitle = dateInfo.view.title;
                        },
                        eventDidMount: (info) => {
                            const type = info.event.extendedProps.unit_type;
                            let bg, border;
                            if (type === 'bungalow') {
                                bg = '#4a5d41';
                                border = '#3a4a34';
                            } else if (type === 'rv') {
                                bg = '#0d9488';
                                border = '#0f766e';
                            } else { // camping
                                bg = '#d97706';
                                border = '#b45309';
                            }
                            info.el.style.backgroundColor = bg;
                            info.el.style.borderColor = border;
                            info.el.style.color = '#ffffff';
                            info.el.classList.add('text-xs', 'font-extrabold', 'shadow-sm', 'cursor-pointer');
                        },
                        moreLinkClick: (info) => {
                            const d = info.date; // FullCalendar date is UTC midnight
                            const dateStr = `${d.getUTCFullYear()}-${String(d.getUTCMonth()+1).padStart(2,'0')}-${String(d.getUTCDate()).padStart(2,'0')}`;
                            this.openDayModal(dateStr);
                            return false; // prevent native FullCalendar popover
                        },
                        dateClick: (info) => {
                            this.openDayModal(info.dateStr);
                        }
                    });
                    
                    calendarInstance.render();
                    this.currentTitle = calendarInstance.view.title;

                    // Force update size after render to resolve Tailwind grid conflicts.
                    setTimeout(() => {
                        if (calendarInstance) {
                            calendarInstance.updateSize();
                        }
                    }, 150);
                },

                prev() {
                    if (calendarInstance) {
                        calendarInstance.prev();
                        this.currentTitle = calendarInstance.view.title;
                    }
                },

                next() {
                    if (calendarInstance) {
                        calendarInstance.next();
                        this.currentTitle = calendarInstance.view.title;
                    }
                },

                setView(viewName) {
                    this.currentView = viewName;
                    if (calendarInstance) {
                        calendarInstance.changeView(viewName);
                        this.currentTitle = calendarInstance.view.title;
                    }
                },

                openDayModal(dateStr) {
                    const events = this.events;

                    // --- Etiqueta dinámica de la sección de entradas ---
                    const todayStr = new Date().toLocaleDateString('sv-SE'); // YYYY-MM-DD local
                    let entradasLabel;
                    if (dateStr === todayStr) {
                        entradasLabel = 'Entradas de hoy';
                    } else if (dateStr > todayStr) {
                        entradasLabel = 'Entradas esperadas';
                    } else {
                        const [y, mo, da] = dateStr.split('-').map(Number);
                        const fechaCorta = new Date(y, mo - 1, da)
                            .toLocaleDateString('es-MX', { day: 'numeric', month: 'long' });
                        entradasLabel = `Entradas del ${fechaCorta}`;
                    }

                    // Entradas: check_in === dateStr — guardar todos los datos para abrir detalle
                    const entradas = events
                        .filter(e => e.extendedProps && e.extendedProps.check_in === dateStr && e.extendedProps.status !== 'checked_out')
                        .map(e => ({ 
                            id: e.id,
                            title: e.title,
                            name: e.extendedProps.guest_name,
                            unit_type: e.extendedProps.unit_type,
                            check_in: e.extendedProps.check_in,
                            check_out: e.extendedProps.check_out,
                            guest_name: e.extendedProps.guest_name,
                            guest_phone: e.extendedProps.guest_phone,
                            unit_name: e.extendedProps.unit_name,
                            total_amount: e.extendedProps.total_amount,
                            status: e.extendedProps.status,
                            expanded: false
                        }));

                    // Estancias vigentes: check_in < dateStr && dateStr <= check_out
                    const estancias = events
                        .filter(e => {
                            if (!e.extendedProps) return false;
                            if (e.extendedProps.status === 'checked_out') return false;
                            // Ignorar estado; si cruza el dia y no es check_in de HOY, es estancia
                            return e.extendedProps.check_in < dateStr && dateStr <= e.extendedProps.check_out;
                        })
                        .map(e => ({
                            id: e.id,
                            title: e.title,
                            name: e.extendedProps.guest_name,
                            unit_type: e.extendedProps.unit_type,
                            check_in: e.extendedProps.check_in,
                            check_out: e.extendedProps.check_out,
                            guest_name: e.extendedProps.guest_name,
                            guest_phone: e.extendedProps.guest_phone,
                            unit_name: e.extendedProps.unit_name,
                            total_amount: e.extendedProps.total_amount,
                            status: e.extendedProps.status,
                            checkinFormatted: this.formatDateShort(e.extendedProps.check_in),
                            checkoutFormatted: this.formatDateShort(e.extendedProps.check_out)
                        }));

                    // Anteriores: checked_out que ocurrieron en este día
                    const anteriores = events
                        .filter(e => {
                            if (!e.extendedProps) return false;
                            if (e.extendedProps.status !== 'checked_out') return false;
                            return e.extendedProps.check_in === dateStr || (e.extendedProps.check_in < dateStr && dateStr <= e.extendedProps.check_out);
                        })
                        .map(e => ({
                            id: e.id,
                            title: e.title,
                            name: e.extendedProps.guest_name,
                            unit_type: e.extendedProps.unit_type,
                            check_in: e.extendedProps.check_in,
                            check_out: e.extendedProps.check_out,
                            guest_name: e.extendedProps.guest_name,
                            guest_phone: e.extendedProps.guest_phone,
                            unit_name: e.extendedProps.unit_name,
                            total_amount: e.extendedProps.total_amount,
                            status: e.extendedProps.status,
                            checkinFormatted: this.formatDateShort(e.extendedProps.check_in),
                            checkoutFormatted: this.formatDateShort(e.extendedProps.check_out)
                        }));

                    // Formatear fecha en español sin problemas de zona horaria
                    const [y, m, d] = dateStr.split('-').map(Number);
                    const dateFormatted = new Date(y, m - 1, d)
                        .toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' });

                    this.selectedDayData = { date: dateStr, dateFormatted, entradasLabel, entradas, estancias, anteriores, showAnteriores: false };
                    this.showDayModal = true;
                },

                // Abre el modal de Detalle de Reserva desde una entrada en el day modal
                openEntradaDetail(item) {
                    this.showDayModal = false;
                    this.selectedEvent = {
                        id: item.id || '',
                        title: item.title || item.guest_name,
                        check_in: item.check_in,
                        check_out: item.check_out,
                        guest_name: item.guest_name,
                        guest_phone: item.guest_phone,
                        unit_name: item.unit_name,
                        unit_type: item.unit_type,
                        total_amount: item.total_amount,
                        status: item.status
                    };
                    this.showModal = true;
                },

                formatDateShort(dateStr) {
                    if (!dateStr) return '';
                    const [year, month, day] = dateStr.split('-');
                    return `${day}/${month}/${year}`;
                },

                openEventDetails(event) {
                    const props = event.extendedProps || event;
                    this.selectedEvent = {
                        id: event.id || '',
                        title: event.title || '',
                        start: event.startStr || props.check_in,
                        end: event.endStr || props.check_out,
                        check_in: props.check_in || (event.startStr ? event.startStr.split('T')[0] : ''),
                        check_out: props.check_out || (event.endStr ? event.endStr.split('T')[0] : ''),
                        guest_name: props.guest_name,
                        guest_phone: props.guest_phone,
                        unit_name: props.unit_name,
                        unit_type: props.unit_type,
                        total_amount: props.total_amount,
                        status: props.status
                    };
                    this.showModal = true;
                }
            };
        }
    </script>
</div>
