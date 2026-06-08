<x-layouts.app>
    <div class="p-6 lg:p-10 max-w-7xl mx-auto">
        <div class="mb-10">
            <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight">Reportes</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-1 font-medium italic">Indicadores operativos y toma de decisiones</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <a href="{{ route('admin.reportes.ocupacion') }}" wire:navigate
               class="group bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-[2.5rem] p-8 shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-start justify-between mb-6">
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl group-hover:scale-110 transition-transform duration-300">
                        <flux:icon name="chart-bar" class="size-8 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <flux:icon name="chevron-right" class="size-6 text-zinc-300 dark:text-zinc-600 group-hover:text-[#4a5d41] transition-colors" />
                </div>
                <h2 class="text-2xl font-black text-zinc-900 dark:text-white mb-2">Ocupación por Día</h2>
                <p class="text-zinc-500 dark:text-zinc-400">Visualiza el porcentaje de ocupación diario en un rango de fechas. Identifica tendencias y picos de demanda.</p>
            </a>

            <a href="{{ route('admin.reportes.historial') }}" wire:navigate
               class="group bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-[2.5rem] p-8 shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-start justify-between mb-6">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-2xl group-hover:scale-110 transition-transform duration-300">
                        <flux:icon name="document-text" class="size-8 text-blue-600 dark:text-blue-400" />
                    </div>
                    <flux:icon name="chevron-right" class="size-6 text-zinc-300 dark:text-zinc-600 group-hover:text-[#4a5d41] transition-colors" />
                </div>
                <h2 class="text-2xl font-black text-zinc-900 dark:text-white mb-2">Historial de Reservaciones</h2>
                <p class="text-zinc-500 dark:text-zinc-400">Consulta el historial completo con filtros por fechas y tipo de unidad. Exporta a CSV para análisis externo.</p>
            </a>
        </div>
    </div>
</x-layouts.app>
