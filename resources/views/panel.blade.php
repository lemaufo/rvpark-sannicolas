<x-layouts.app>
    <div class="p-6 lg:p-10 max-w-7xl mx-auto">
        <div class="mb-8 flex items-end justify-between">
            <div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Panel Principal</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mt-1">Resumen para {{ now()->translatedFormat('l, d \d\e F Y') }}</p>
            </div>
            <button class="bg-brand-green text-white px-5 py-2.5 rounded-xl font-semibold shadow-lg shadow-brand-green/20 hover:bg-brand-green-dark transition-all duration-200 flex items-center gap-2">
                <flux:icon name="plus" class="size-5" />
                <span>Nueva Reserva</span>
            </button>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            @php
                $stats = [
                    ['label' => 'Ocupación Hoy', 'value' => '82%', 'change' => '+5%', 'icon' => 'home', 'color' => 'text-emerald-500', 'bg' => 'bg-zinc-50'],
                    ['label' => 'Unidades Disponibles', 'value' => '5', 'sub' => 'de 28', 'icon' => 'users', 'bg' => 'bg-zinc-50'],
                    ['label' => 'Entradas Hoy', 'value' => '3', 'sub' => '2 pendientes', 'icon' => 'arrow-right-start-on-rectangle', 'bg' => 'bg-zinc-50'],
                    ['label' => 'Ingresos Diarios', 'value' => '$2,450', 'change' => '+12%', 'icon' => 'currency-dollar', 'color' => 'text-emerald-500', 'bg' => 'bg-zinc-50'],
                ];
            @endphp

            @foreach($stats as $stat)
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-sm group hover:border-brand-green/30 transition-all duration-300">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400 text-sm font-medium">{{ $stat['label'] }}</p>
                            <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $stat['value'] }}</h3>
                            @isset($stat['change'])
                                <p class="{{ $stat['color'] ?? 'text-zinc-400' }} text-xs font-bold mt-1.5 flex items-center gap-1">
                                    <span>{{ $stat['change'] }}</span>
                                </p>
                            @endisset
                            @isset($stat['sub'])
                                <p class="text-zinc-400 dark:text-zinc-500 text-xs font-medium mt-1">{{ $stat['sub'] }}</p>
                            @endisset
                        </div>
                        <div class="p-3 {{ $stat['bg'] ?? 'bg-zinc-50' }} dark:bg-zinc-800 rounded-xl group-hover:bg-zinc-100 dark:group-hover:bg-zinc-700 transition-colors">
                            <flux:icon :name="$stat['icon']" class="size-6 text-zinc-400 dark:text-zinc-500 transition-colors" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Ocupación en Vivo</h2>
                        <a href="#" class="text-brand-green text-sm font-bold hover:underline underline-offset-4 flex items-center gap-1">
                            Ver Todas 
                            <flux:icon name="chevron-right" class="size-4" />
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl bg-zinc-50/30 dark:bg-zinc-800/30 flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-zinc-900 dark:text-white">Bungalow Jade</h4>
                                <p class="text-xs text-zinc-500">Bungalow</p>
                                <span class="inline-block mt-3 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-[10px] font-bold rounded-md uppercase tracking-wider">Ocupado</span>
                            </div>
                            <div class="size-2 rounded-full bg-red-500"></div>
                        </div>
                        <div class="p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl bg-zinc-50/30 dark:bg-zinc-800/30 flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-zinc-900 dark:text-white">Bungalow Coral</h4>
                                <p class="text-xs text-zinc-500">Bungalow</p>
                                <span class="inline-block mt-3 px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold rounded-md uppercase tracking-wider">Disponible</span>
                            </div>
                            <div class="size-2 rounded-full bg-emerald-500"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-8 shadow-sm">
                    <div class="flex items-center gap-2 mb-6">
                        <flux:icon name="bell" class="size-5 text-zinc-400" />
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Notificaciones</h2>
                    </div>
                    <div class="space-y-4">
                        <div class="flex gap-4 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                            <div class="size-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                                <flux:icon name="exclamation-circle" class="size-4 text-zinc-500 dark:text-zinc-400" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Recordatorio de salida</p>
                                <p class="text-[10px] text-zinc-400 font-bold mt-1">HACE 1 HORA</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
