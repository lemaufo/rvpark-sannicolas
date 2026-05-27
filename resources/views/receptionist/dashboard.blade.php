<x-layouts.app>
    <div class="p-6 lg:p-10 max-w-7xl mx-auto">
        <div class="mb-10 flex items-end justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight">Dashboard Recepcionista</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mt-1 font-medium italic">Resumen para {{ now()->translatedFormat('l, d \d\e F Y') }}</p>
            </div>
        </div>

        <livewire:receptionist-panel />
    </div>
</x-layouts.app>
