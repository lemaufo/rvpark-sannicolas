<x-layouts.admin active="dashboard">
    <div class="mb-10 flex items-end justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-zinc-900 tracking-tight">Dashboard Administrador</h1>
            <p class="text-zinc-500 mt-1 font-medium italic">Resumen para {{ now()->translatedFormat('l, d \d\e F Y') }}</p>
        </div>
        <button class="bg-[#4a5d41] text-white px-6 py-3 rounded-2xl font-bold shadow-xl shadow-brand-green/20 hover:scale-[1.02] transition-all duration-200 flex items-center gap-2.5">
            <flux:icon name="plus" class="size-5" />
            <span>Nueva Reserva</span>
        </button>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        @php
            $stats = [
                ['label' => 'Ocupación Hoy',        'value' => $porcentajeOcupacion . '%',       'change' => $unidadesOcupadas . ' de ' . $totalUnidades . ' unidades', 'icon' => 'home',                          'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/30'],
                ['label' => 'Unidades Disponibles',  'value' => (string) $totalDisponibles,        'sub'    => 'de ' . $totalUnidades,                                    'icon' => 'users',                         'color' => 'text-zinc-400',    'bg' => 'bg-zinc-50 dark:bg-zinc-800'],
                ['label' => 'Entradas Hoy',          'value' => (string) $checkinsHoy->count(),    'sub'    => $checkoutsHoy->count() . ' salidas hoy',                  'icon' => 'arrow-right-start-on-rectangle', 'color' => 'text-zinc-400',    'bg' => 'bg-zinc-50 dark:bg-zinc-800'],
                ['label' => 'Ingresos Diarios',      'value' => '$2,450',                          'change' => '+12%',                                                   'icon' => 'currency-dollar',               'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/30'],
            ];
        @endphp

        @foreach($stats as $stat)
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-[2rem] p-7 shadow-sm hover:shadow-md transition-shadow duration-300 group">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-zinc-400 dark:text-zinc-500 text-[13px] font-bold uppercase tracking-wider">{{ $stat['label'] }}</p>
                        <h3 class="text-3xl font-black text-zinc-900 dark:text-white mt-2">{{ $stat['value'] }}</h3>
                        @isset($stat['change'])
                            <p class="{{ $stat['color'] }} text-xs font-bold mt-1.5">{{ $stat['change'] }}</p>
                        @endisset
                        @isset($stat['sub'])
                            <p class="text-zinc-400 dark:text-zinc-500 text-xs font-bold mt-1.5">{{ $stat['sub'] }}</p>
                        @endisset
                    </div>
                    <div class="p-4 {{ $stat['bg'] }} rounded-2xl group-hover:scale-110 transition-transform duration-300">
                        <flux:icon :name="$stat['icon']" class="size-7 text-zinc-800 dark:text-zinc-200" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Ocupación en Vivo --}}
    <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-[2.5rem] p-8 shadow-sm mb-10">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-black text-zinc-900 dark:text-white tracking-tight">Ocupación en Vivo</h2>
            <a href="#" class="text-[#4a5d41] text-sm font-bold hover:underline underline-offset-4 flex items-center gap-1">
                Ver Todas 
                <flux:icon name="chevron-right" class="size-4" />
            </a>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Entradas de Hoy (Llegadas) --}}
            @foreach($checkinsHoy as $reserva)
                <div class="p-6 border border-zinc-50 dark:border-zinc-800 rounded-3xl bg-[#fcfcfc] dark:bg-zinc-800/50 flex justify-between items-start group hover:border-brand-green/20 transition-colors">
                    <div>
                        <h4 class="font-extrabold text-zinc-900 dark:text-white text-lg">{{ $reserva->unit?->name ?? 'Bungalow Sin Asignar' }}</h4>
                        <p class="text-xs text-zinc-400 font-bold uppercase tracking-widest mt-0.5">{{ $reserva->unit?->type ?? 'Bungalow' }}</p>
                        <span class="inline-block mt-4 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-[10px] font-black rounded-xl uppercase tracking-widest">Disponible / Entrada</span>
                        <div class="flex items-center gap-2 mt-4">
                            <div class="size-6 rounded-full bg-zinc-200 border-2 border-white dark:border-zinc-700"></div>
                            <p class="text-xs text-zinc-600 dark:text-zinc-300 font-bold">{{ $reserva->guest_name }}</p>
                        </div>
                    </div>
                    <div class="size-3 rounded-full bg-emerald-500 shadow-lg shadow-emerald-200 dark:shadow-none"></div>
                </div>
            @endforeach

            {{-- Salidas de Hoy (Salidas) --}}
            @foreach($checkoutsHoy as $reserva)
                <div class="p-6 border border-zinc-50 dark:border-zinc-800 rounded-3xl bg-[#fcfcfc] dark:bg-zinc-800/50 flex justify-between items-start group hover:border-brand-green/20 transition-colors">
                    <div>
                        <h4 class="font-extrabold text-zinc-900 dark:text-white text-lg">{{ $reserva->unit?->name ?? 'Bungalow Sin Asignar' }}</h4>
                        <p class="text-xs text-zinc-400 font-bold uppercase tracking-widest mt-0.5">{{ $reserva->unit?->type ?? 'Bungalow' }}</p>
                        <span class="inline-block mt-4 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-[10px] font-black rounded-xl uppercase tracking-widest">Ocupado / Salida</span>
                        <div class="flex items-center gap-2 mt-4">
                            <div class="size-6 rounded-full bg-zinc-200 border-2 border-white dark:border-zinc-700"></div>
                            <p class="text-xs text-zinc-600 dark:text-zinc-300 font-bold">{{ $reserva->guest_name }}</p>
                        </div>
                    </div>
                    <div class="size-3 rounded-full bg-red-500 shadow-lg shadow-red-200 dark:shadow-none"></div>
                </div>
            @endforeach

            @if($checkinsHoy->isEmpty() && $checkoutsHoy->isEmpty())
                <div class="col-span-full py-8 text-center border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-3xl">
                    <p class="text-zinc-500 font-medium">No hay entradas ni salidas programadas para hoy.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Rol badge --}}
    <div class="flex items-center gap-3 mb-8">
        <span class="px-4 py-1.5 bg-[#4a5d41] text-white text-xs font-black uppercase tracking-widest rounded-full">
            Administrador
        </span>
        <span class="text-zinc-400 dark:text-zinc-500 text-sm font-medium">Acceso completo al sistema</span>
    </div>
</x-layouts.admin>
