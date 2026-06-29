<x-layouts.app>
    <div class="p-4 sm:p-6 lg:p-10 max-w-7xl mx-auto">
        <div class="mb-6 sm:mb-10 flex items-end justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight">Panel de Recepcionista</h1>
                <p class="text-red-600 dark:text-red-400 mt-1 font-semibold italic text-sm sm:text-base">Resumen para {{ now()->translatedFormat('l, d \d\e F Y') }}</p>
            </div>
        </div>

        <livewire:receptionist-panel />
    </div>
</x-layouts.app>
