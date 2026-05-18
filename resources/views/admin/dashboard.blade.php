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
                ['label' => 'Ocupación Hoy', 'value' => '82%', 'change' => '+5%', 'icon' => 'home', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/30'],
                ['label' => 'Unidades Disponibles', 'value' => '5', 'sub' => 'de 28', 'icon' => 'users', 'color' => 'text-zinc-400', 'bg' => 'bg-zinc-50 dark:bg-zinc-800'],
                ['label' => 'Entradas Hoy', 'value' => '3', 'sub' => '2 pendientes', 'icon' => 'arrow-right-start-on-rectangle', 'color' => 'text-zinc-400', 'bg' => 'bg-zinc-50 dark:bg-zinc-800'],
                ['label' => 'Ingresos Diarios', 'value' => '$2,450', 'change' => '+12%', 'icon' => 'currency-dollar', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/30'],
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

    {{-- Rol badge --}}
    <div class="flex items-center gap-3 mb-8">
        <span class="px-4 py-1.5 bg-[#4a5d41] text-white text-xs font-black uppercase tracking-widest rounded-full">
            Administrador
        </span>
        <span class="text-zinc-400 dark:text-zinc-500 text-sm font-medium">Acceso completo al sistema</span>
    </div>
</x-layouts.admin>
