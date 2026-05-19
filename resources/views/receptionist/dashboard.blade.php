<x-layouts.app>
    <div class="p-6 lg:p-10 max-w-7xl mx-auto">
        <div class="mb-10 flex items-end justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight">Dashboard Recepcionista</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mt-1 font-medium italic">Resumen para {{ now()->translatedFormat('l, d \d\e F Y') }}</p>
            </div>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            @php
                $today = \Carbon\Carbon::today();

                // ── Entradas Hoy (Check-ins) ──────────────────────────────────
                // Total programado/efectuado para hoy (excluyendo cancelados)
                $checkinsTodayTotal = \App\Models\Reservation::whereDate('check_in', $today)
                    ->where('status', '!=', 'cancelled')
                    ->count();

                // Pendientes (aún no hacen ingreso físico/check_in)
                $checkinsPending = \App\Models\Reservation::whereDate('check_in', $today)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count();

                // ── Salidas Hoy (Check-outs) ──────────────────────────────────
                // Total programado/efectuado para hoy (excluyendo cancelados y pendientes sin confirmar)
                $checkoutsTodayTotal = \App\Models\Reservation::whereDate('check_out', $today)
                    ->whereNotIn('status', ['cancelled', 'pending'])
                    ->count();

                // Pendientes (tienen huésped alojado o confirmado que no ha salido)
                $checkoutsPending = \App\Models\Reservation::whereDate('check_out', $today)
                    ->whereIn('status', ['confirmed', 'checked_in'])
                    ->count();

                // ── Unidades Disponibles ──────────────────────────────────────
                $availableUnits = \App\Models\Unit::where('status', 'available')->count();
                $totalUnits     = \App\Models\Unit::count();

                $stats = [
                    [
                        'label' => 'Entradas Hoy',
                        'value' => (string) $checkinsTodayTotal,
                        'sub'   => $checkinsPending . ' pendiente' . ($checkinsPending !== 1 ? 's' : ''),
                        'icon'  => 'arrow-right-start-on-rectangle',
                        'color' => 'text-zinc-400',
                        'bg'    => 'bg-zinc-50 dark:bg-zinc-800',
                    ],
                    [
                        'label' => 'Salidas Hoy',
                        'value' => (string) $checkoutsTodayTotal,
                        'sub'   => $checkoutsPending . ' pendiente' . ($checkoutsPending !== 1 ? 's' : ''),
                        'icon'  => 'arrow-left-start-on-rectangle',
                        'color' => 'text-zinc-400',
                        'bg'    => 'bg-zinc-50 dark:bg-zinc-800',
                    ],
                    [
                        'label' => 'Unidades Disponibles',
                        'value' => (string) $availableUnits,
                        'sub'   => 'de ' . $totalUnits,
                        'icon'  => 'home',
                        'color' => 'text-emerald-500',
                        'bg'    => 'bg-emerald-50 dark:bg-emerald-900/30',
                    ],
                ];
            @endphp

            @foreach($stats as $stat)
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-[2rem] p-7 shadow-sm hover:shadow-md transition-shadow duration-300 group">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-zinc-400 dark:text-zinc-500 text-[13px] font-bold uppercase tracking-wider">{{ $stat['label'] }}</p>
                            <h3 class="text-2xl font-black text-zinc-900 dark:text-white mt-2">{{ $stat['value'] }}</h3>
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
            <span class="px-4 py-1.5 bg-zinc-700 dark:bg-zinc-800 text-white text-xs font-black uppercase tracking-widest rounded-full">
                Recepcionista
            </span>
            <span class="text-zinc-400 dark:text-zinc-500 text-sm font-medium">Gestión de reservas y check-in</span>
        </div>
    </div>
</x-layouts.app>
