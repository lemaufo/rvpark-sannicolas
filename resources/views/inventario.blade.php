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

        <livewire:inventory-manager />
    </div>
</x-layouts.app>
