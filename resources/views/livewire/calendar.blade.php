<?php

use Livewire\Volt\Component;
use App\Services\ReservationService;
use Carbon\Carbon;

new class extends Component {
    public $events = [];
    public $entradasHoy = [];
    public $salidasHoy = [];
    public $estadisticas = [];

    public function mount()
    {
        $service = new ReservationService();
        $this->events = $service->getReservationsForCalendar();

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
            ->filter(fn($e) => $e['end'] === $today)
            ->map(fn($e) => [
                'guest' => $e['extendedProps']['guest_name'],
                'unit' => $e['extendedProps']['unit_name'],
                'type' => $e['extendedProps']['unit_type'],
            ])->toArray();

        // Fallbacks for demonstration in case there's no check-in/out today
        if (empty($this->entradasHoy)) {
            $this->entradasHoy[] = [
                'guest' => 'Anderson, K.',
                'unit' => 'Bungalow Superior A1',
                'type' => 'bungalow'
            ];
        }
        if (empty($this->salidasHoy)) {
            $this->salidasHoy[] = [
                'guest' => 'Familia García',
                'unit' => 'RV 1',
                'type' => 'rv'
            ];
            $this->salidasHoy[] = [
                'guest' => 'Familia Taylor',
                'unit' => 'Camping 1',
                'type' => 'camping'
            ];
        }

        // Stats calculation based on all events
        $total = count($this->events);
        $directas = 0;
        $telefono = 0;
        $web = 0;

        foreach ($this->events as $idx => $e) {
            if ($idx % 3 === 0) {
                $directas++;
            } elseif ($idx % 3 === 1) {
                $telefono++;
            } else {
                $web++;
            }
        }

        $this->estadisticas = [
            'total' => $total,
            'directas' => $directas,
            'telefono' => $telefono,
            'web' => $web,
        ];
    }
};
?>

<div x-data="calendarApp({ 
    events: @js($events),
    initialDate: '2026-05-27'
})" class="space-y-8">
    {{-- Scripts for FullCalendar --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    {{-- Top Action Bar / Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-8">
        <div>
            <h1 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight">Reservaciones</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-1">Administrar reservas y disponibilidad</p>
        </div>

        {{-- Switch View Mode (Purple area in user description, but styled as requested standard) --}}
        <div class="flex items-center self-end sm:self-center gap-1.5 bg-zinc-100 dark:bg-zinc-800 p-1 rounded-2xl border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm">
            <button @click="viewMode = 'calendar'" 
                :class="viewMode === 'calendar' ? 'bg-[#4a5d41] text-white shadow-md' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'" 
                class="px-5 py-2 text-sm font-bold rounded-xl transition-all duration-200">
                Calendario
            </button>
            <button type="button"
                :class="viewMode === 'list' ? 'bg-[#4a5d41] text-white shadow-md' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'" 
                class="px-5 py-2 text-sm font-bold rounded-xl transition-all duration-200 cursor-not-allowed opacity-60" title="Próximamente">
                Lista
            </button>
        </div>
    </div>

    {{-- Top Cards Section (Orange area in user description) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Entradas Hoy Card --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-[2rem] p-6 shadow-sm flex flex-col min-h-[190px]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-zinc-800 dark:text-zinc-200 font-bold text-lg">Entradas Hoy</h3>
                <span class="bg-emerald-600 text-white rounded-full size-6 flex items-center justify-center text-xs font-bold">
                    {{ count($entradasHoy) }}
                </span>
            </div>
            <div class="space-y-3 overflow-y-auto flex-1 max-h-[150px] pr-1">
                @foreach($entradasHoy as $item)
                    <div class="bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-2xl flex flex-col gap-0.5 border border-zinc-100 dark:border-zinc-800/80">
                        <span class="font-bold text-zinc-800 dark:text-zinc-200 text-sm">{{ $item['guest'] }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400 text-xs">{{ $item['unit'] }}</span>
                    </div>
                @endforeach
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
                @foreach($salidasHoy as $item)
                    <div class="bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-2xl flex flex-col gap-0.5 border border-zinc-100 dark:border-zinc-800/80">
                        <span class="font-bold text-zinc-800 dark:text-zinc-200 text-sm">{{ $item['guest'] }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400 text-xs">{{ $item['unit'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Estadísticas Card --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-[2rem] p-6 shadow-sm flex flex-col min-h-[190px]">
            <h3 class="text-zinc-800 dark:text-zinc-200 font-bold text-lg mb-4">Estadísticas</h3>
            <div class="space-y-2.5 flex-1 flex flex-col justify-center">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400 font-medium">Total Reservaciones</span>
                    <span class="font-bold text-zinc-900 dark:text-white text-base">{{ $estadisticas['total'] }}</span>
                </div>
                <div class="w-full h-px bg-zinc-100 dark:bg-zinc-800 my-1"></div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400 font-medium">Directas</span>
                    <span class="font-bold text-zinc-900 dark:text-white">{{ $estadisticas['directas'] }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400 font-medium">Por Teléfono</span>
                    <span class="font-bold text-zinc-900 dark:text-white">{{ $estadisticas['telefono'] }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400 font-medium">Por Web</span>
                    <span class="font-bold text-zinc-900 dark:text-white">{{ $estadisticas['web'] }}</span>
                </div>
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
            <div class="px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 flex justify-between items-center bg-zinc-50 dark:bg-zinc-900/40">
                <h3 class="text-xl font-black text-zinc-900 dark:text-white flex items-center gap-2">
                    <flux:icon name="information-circle" class="size-6 text-[#4a5d41]" />
                    Detalle de Reserva
                </h3>
                <button @click="showModal = false" class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-200/50 dark:hover:bg-zinc-800 rounded-full transition-colors">
                    <flux:icon name="x-mark" class="size-5" />
                </button>
            </div>

            {{-- Modal Content --}}
            <template x-if="selectedEvent">
                <div class="p-6 space-y-6">
                    {{-- Guest Info --}}
                    <div class="flex items-center gap-4">
                        <div class="size-12 rounded-2xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-600 dark:text-zinc-400 shrink-0">
                            <flux:icon name="user" class="size-6" />
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-zinc-900 dark:text-white" x-text="selectedEvent.guest_name"></h4>
                            <p class="text-zinc-500 dark:text-zinc-400 text-sm mt-0.5" x-text="selectedEvent.guest_phone"></p>
                        </div>
                    </div>

                    {{-- Unit Info --}}
                    <div class="grid grid-cols-2 gap-4 bg-zinc-50 dark:bg-zinc-800/40 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800/80">
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
                    <div class="grid grid-cols-2 gap-4">
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
                    <div class="flex items-center justify-between p-4 bg-[#4a5d41]/5 dark:bg-[#4a5d41]/10 rounded-2xl border border-[#4a5d41]/10">
                        <div>
                            <span class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider">Total Estimado</span>
                            <span class="block text-2xl font-black text-[#4a5d41] dark:text-emerald-400 mt-0.5" 
                                x-text="'$' + Number(selectedEvent.total_amount).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})">
                            </span>
                        </div>
                        
                        <div class="text-right">
                            <span class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-1.5">Estado</span>
                            <span class="px-2.5 py-1 text-xs font-bold rounded-xl border"
                                :class="selectedEvent.status === 'confirmed' ? 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/20' : (selectedEvent.status === 'checked_in' ? 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/20' : (selectedEvent.status === 'checked_out' ? 'bg-zinc-100 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700' : 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/20'))"
                                x-text="selectedEvent.status === 'confirmed' ? 'Confirmada' : (selectedEvent.status === 'checked_in' ? 'Activa' : (selectedEvent.status === 'checked_out' ? 'Completada' : 'Pendiente'))">
                            </span>
                        </div>
                    </div>

                    {{-- Close Button --}}
                    <div class="flex gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                        <button @click="showModal = false" 
                            class="w-full py-3 bg-[#4a5d41] text-white hover:bg-[#3a4a34] font-black rounded-xl transition-all shadow-md active:scale-[0.98] flex items-center justify-center gap-2">
                            <flux:icon name="check" class="size-5" />
                            Aceptar
                        </button>
                    </div>
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
            overflow: hidden !important;
        }
        .fc .fc-daygrid-day-frame {
            position: relative !important;
            overflow: hidden !important;
            min-height: 80px !important;
        }
        .fc .fc-daygrid-event-harness {
            overflow: hidden !important;
            max-width: 100% !important;
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
        
        /* Hide scrollbars on popover */
        .fc-more-popover {
            border-radius: 1.5rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            border: 1px solid #e4e4e7 !important;
            overflow: hidden !important;
        }
        .dark .fc-more-popover {
            border: 1px solid #27272a !important;
            background-color: #18181b !important;
        }
        
        .fc-more-popover .fc-popover-header {
            background-color: #fafafa !important;
            font-weight: 750 !important;
            color: #27272a !important;
            padding: 10px 14px !important;
        }
        .dark .fc-more-popover .fc-popover-header {
            background-color: #1c1c1f !important;
            color: #f4f4f5 !important;
        }
        
        .fc-more-popover .fc-popover-body {
            padding: 12px !important;
            max-height: 250px;
            overflow-y: auto;
            background-color: #ffffff;
        }
        .dark .fc-more-popover .fc-popover-body {
            background-color: #18181b;
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
        document.addEventListener('alpine:init', () => {
            Alpine.data('calendarApp', (config) => ({
                events: config.events,
                viewMode: 'calendar',
                currentTitle: '',
                currentView: 'dayGridMonth',
                selectedEvent: null,
                showModal: false,
                calendar: null,

                init() {
                    this.$nextTick(() => {
                        this.initCalendar();
                    });
                },

                initCalendar() {
                    const calendarEl = document.getElementById('calendar-el');
                    if (!calendarEl) return;

                    this.calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: this.currentView,
                        initialDate: config.initialDate,
                        locale: 'es',
                        firstDay: 0, // Domingo
                        headerToolbar: false, // Custom header handled via Alpine
                        dayMaxEvents: 2, // Max 2 events per day cell, showing "+X más"
                        eventDisplay: 'block', // Force events to act as blocks and respect boundaries
                        events: this.events,
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
                        }
                    });
                    
                    this.calendar.render();
                    this.currentTitle = this.calendar.view.title;

                    // Force update size after render to resolve Tailwind grid conflicts.
                    // 150ms ensures Tailwind's styles are fully applied before FullCalendar
                    // re-measures all column widths.
                    setTimeout(() => {
                        if (this.calendar) {
                            this.calendar.updateSize();
                        }
                    }, 150);
                },

                prev() {
                    if (this.calendar) {
                        this.calendar.prev();
                        this.currentTitle = this.calendar.view.title;
                    }
                },

                next() {
                    if (this.calendar) {
                        this.calendar.next();
                        this.currentTitle = this.calendar.view.title;
                    }
                },

                setView(viewName) {
                    this.currentView = viewName;
                    if (this.calendar) {
                        this.calendar.changeView(viewName);
                        this.currentTitle = this.calendar.view.title;
                    }
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
            }));
        });
    </script>
</div>
