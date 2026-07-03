<?php

use Livewire\Volt\Component;
use App\Models\Unit;
use App\Models\OperationalStatus;

new class extends Component {
    public $cleaningUnits = [];
    public $cleanedToday = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        // Obtener unidades que necesitan limpieza
        $this->cleaningUnits = Unit::where('status', 'cleaning')
            ->orderBy('name')
            ->get();

        // Obtener unidades limpiadas hoy por cualquier usuario
        $this->cleanedToday = OperationalStatus::where('status', 'available')
            ->whereDate('changed_at', now()->toDateString())
            ->with(['unit', 'user'])
            ->orderBy('changed_at', 'desc')
            ->get();
    }

    public function markAsClean($unitId)
    {
        try {
            $unit = Unit::find($unitId);
            if ($unit && $unit->status === 'cleaning') {
                // Actualizar estado de la unidad
                $unit->update(['status' => 'available']);

                // Crear registro de estado operacional
                OperationalStatus::create([
                    'unit_id' => $unit->id,
                    'status' => 'available',
                    'user_id' => auth()->id(),
                    'changed_at' => now()
                ]);

                $this->loadData();
                $this->dispatch('swal-success', [
                    'title' => 'Unidad Limpia',
                    'message' => "La unidad {$unit->name} ha sido marcada como limpia y disponible."
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo actualizar el estado de la unidad.');
        }
    }
};
?>

<div class="space-y-8">
    {{-- Grid principal --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Listado de unidades pendientes de limpieza (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-[2.5rem] p-6 sm:p-8 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-white tracking-tight">Unidades por Limpiar</h2>
                        <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1 font-semibold">
                            Total pendiente: {{ count($cleaningUnits) }}
                        </p>
                    </div>
                    <button wire:click="loadData" class="p-2 bg-zinc-50 dark:bg-zinc-850 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors text-zinc-500 dark:text-zinc-400" title="Actualizar">
                        <flux:icon name="arrow-path" class="size-5" />
                    </button>
                </div>

                @if(count($cleaningUnits) > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($cleaningUnits as $unit)
                            <div class="p-5 border border-zinc-200/60 dark:border-zinc-800 rounded-3xl bg-zinc-50 dark:bg-zinc-800/40 flex flex-col justify-between hover:border-[#4a5d41]/30 transition-all duration-200 group">
                                <div class="space-y-2">
                                    <div class="flex justify-between items-start">
                                        <h4 class="font-extrabold text-zinc-900 dark:text-white text-base sm:text-lg">{{ $unit->name }}</h4>
                                        <span class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-widest rounded-md bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                            {{ $unit->type }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">
                                        {{ $unit->specifications ?: 'Sin especificaciones' }}
                                    </p>
                                </div>

                                <div class="mt-6 pt-4 border-t border-zinc-200/50 dark:border-zinc-700/50">
                                    <button wire:click="markAsClean({{ $unit->id }})" class="w-full py-2.5 bg-[#4a5d41] hover:bg-[#3d4d35] text-white text-sm font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 select-none active:scale-[0.98]">
                                        <flux:icon name="check-circle" class="size-4" />
                                        Marcar como Limpia
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-12 text-center border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-3xl">
                        <flux:icon name="sparkles" class="size-12 text-emerald-500 mx-auto mb-3" />
                        <p class="text-zinc-500 dark:text-zinc-400 font-extrabold text-base">¡Todas las unidades están limpias!</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">No hay unidades en cola de limpieza en este momento.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Historial de limpieza de hoy (1/3) --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-[2.5rem] p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl">
                        <flux:icon name="check-badge" class="size-5 text-emerald-600 dark:text-emerald-500" />
                    </div>
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-zinc-900 dark:text-white tracking-tight">Limpias Hoy</h2>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 font-semibold uppercase tracking-wider">Historial Reciente</p>
                    </div>
                </div>

                <div class="space-y-4 max-h-[400px] overflow-y-auto desktop-scrollbar pr-1">
                    @forelse($cleanedToday as $log)
                        <div class="flex gap-4 p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/30 border border-zinc-100 dark:border-zinc-800/80 items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="size-9 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                                    <flux:icon name="home" class="size-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ $log->unit->name ?? 'N/A' }}</p>
                                    <p class="text-[10px] text-zinc-400 dark:text-zinc-500 font-semibold truncate">Por: {{ $log->user->name ?? 'Sistema' }}</p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ \Carbon\Carbon::parse($log->changed_at)->format('H:i') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-zinc-500 dark:text-zinc-500 text-xs py-6 text-center border-2 border-dashed border-zinc-100 dark:border-zinc-800 rounded-2xl">No se han registrado limpiezas hoy.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
