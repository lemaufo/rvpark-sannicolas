<x-layouts.app>
    <div class="p-6 lg:p-10 max-w-7xl mx-auto">
        <div class="mb-10 flex items-end justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight">Inventario de Unidades</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mt-1 font-medium italic">Gestión de Bungalows, RV Spots y Áreas de Camping</p>
            </div>
            <div class="flex gap-3">
                <button class="bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-800 px-6 py-3 rounded-2xl font-bold hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-all flex items-center gap-2">
                    <flux:icon name="funnel" class="size-5" />
                    <span>Filtrar</span>
                </button>
                <button class="bg-[#4a5d41] text-white px-6 py-3 rounded-2xl font-bold shadow-xl shadow-brand-green/20 hover:scale-[1.02] transition-all flex items-center gap-2">
                    <flux:icon name="plus" class="size-5" />
                    <span>Nueva Unidad</span>
                </button>
            </div>
        </div>

        <!-- Inventory Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            @php
                $units = \App\Models\Unit::all()->map(function ($unit) {
                    return [
                        'name' => $unit->name,
                        'type' => ucfirst($unit->type),
                        'status' => match($unit->status) {
                            'available' => 'Disponible',
                            'occupied' => 'Ocupado',
                            'cleaning' => 'Limpieza',
                            default => $unit->status
                        },
                        'status_color' => match($unit->status) {
                            'available' => 'green',
                            'occupied' => 'red',
                            'cleaning' => 'orange',
                            default => 'zinc'
                        },
                        'guest' => null, // No hay relación de huéspedes aún
                        'details' => $unit->notes ?? 'Sin detalles adicionales'
                    ];
                });
            @endphp

            @forelse($units as $unit)
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-[2rem] p-6 shadow-sm hover:shadow-md transition-shadow duration-300">
                    <!-- Thumbnail Area (Landscape) -->
                    <div class="relative aspect-[16/11] w-full bg-[#EBE7E0] dark:bg-zinc-800 rounded-[1.5rem] flex items-center justify-end px-6 mb-6 overflow-hidden">
                        <!-- Status Badge Overlay (Right Aligned) -->
                        <span class="inline-flex items-center px-3 py-1 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-sm text-[10px] font-black uppercase tracking-widest text-{{ $unit['status_color'] }}-600 rounded-full shadow-sm">
                            {{ $unit['status'] }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h3 class="text-xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $unit['name'] }}</h3>
                            <p class="text-xs text-zinc-400 font-bold uppercase tracking-widest mt-1">{{ $unit['type'] }}</p>
                        </div>

                        <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 space-y-3">
                            <div class="flex items-start gap-2">
                                <flux:icon name="information-circle" class="size-4 text-zinc-300 mt-0.5" />
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $unit['details'] }}</p>
                            </div>
                        </div>

                        <button class="w-full py-3 bg-[#4a5d41] text-white text-[11px] font-black uppercase tracking-widest rounded-xl hover:bg-[#3a4a34] transition-colors shadow-lg shadow-brand-green/10">
                            Gestionar Unidad
                        </button>
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
    </div>
</x-layouts.app>
