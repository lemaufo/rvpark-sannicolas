<x-layouts.app>
    <div class="p-6 lg:p-10 max-w-7xl mx-auto space-y-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight">Ocupación por Día</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mt-1 font-medium italic">{{ number_format(count($data)) }} días registrados</p>
            </div>
            <a href="{{ route('admin.reportes.index') }}" wire:navigate
               class="text-sm font-bold text-[#4a5d41] hover:underline underline-offset-4 flex items-center gap-1">
                <flux:icon name="arrow-left" class="size-4" />
                Volver a Reportes
            </a>
        </div>

        {{-- Summary KPIs --}}
        @php
            $ocupaciones = array_column($data, 'porcentaje');
            $promedio = count($ocupaciones) > 0 ? round(array_sum($ocupaciones) / count($ocupaciones), 1) : 0;
            $maxRow = collect($data)->sortByDesc('porcentaje')->first();
            $minRow = collect($data)->filter(fn($d) => $d['porcentaje'] > 0)->sortBy('porcentaje')->first();
            $totalOcupadas = collect($data)->sum('ocupadas');
            $diasSobre80 = collect($data)->filter(fn($d) => $d['porcentaje'] >= 80)->count();
            $diasVacios = collect($data)->filter(fn($d) => $d['ocupadas'] === 0)->count();
        @endphp

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1">Promedio</p>
                <p class="text-3xl font-black {{ $promedio > 80 ? 'text-red-500' : ($promedio > 50 ? 'text-amber-500' : 'text-emerald-500') }}">
                    {{ $promedio }}<span class="text-lg">%</span>
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1">Día pico</p>
                @if($maxRow)
                    <p class="text-3xl font-black text-amber-500">{{ $maxRow['porcentaje'] }}<span class="text-lg">%</span></p>
                    <p class="text-xs text-zinc-400 mt-0.5">{{ \Carbon\Carbon::parse($maxRow['fecha'])->format('d M') }}</p>
                @else
                    <p class="text-zinc-400 text-sm">—</p>
                @endif
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1">Día más bajo</p>
                @if($minRow)
                    <p class="text-3xl font-black text-zinc-700 dark:text-zinc-300">{{ $minRow['porcentaje'] }}<span class="text-lg">%</span></p>
                    <p class="text-xs text-zinc-400 mt-0.5">{{ \Carbon\Carbon::parse($minRow['fecha'])->format('d M') }}</p>
                @else
                    <p class="text-zinc-400 text-sm">—</p>
                @endif
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1">Días a tope</p>
                <p class="text-3xl font-black {{ $diasSobre80 > 0 ? 'text-red-500' : 'text-zinc-500' }}">{{ $diasSobre80 }}</p>
                <p class="text-xs text-zinc-400 mt-0.5">≥ 80% ocupación</p>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1">Días sin reservas</p>
                <p class="text-3xl font-black text-zinc-500">{{ $diasVacios }}</p>
                <p class="text-xs text-zinc-400 mt-0.5">0% ocupación</p>
            </div>
        </div>

        {{-- Filter + Chart Card --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-[2.5rem] shadow-sm overflow-hidden">
            {{-- Filter bar --}}
            <div class="px-8 pt-8 pb-6 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-zinc-900 dark:text-white">Gráfica de Ocupación</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $totalUnits }} unidades en total</p>
                    </div>

                    <form method="GET" action="{{ route('admin.reportes.ocupacion') }}" class="flex flex-wrap items-end gap-2">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-400 mb-1">Desde</label>
                            <input type="date" name="desde" value="{{ $startDate }}"
                                   class="rounded-xl border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-[#4a5d41]/20 focus:border-[#4a5d41] px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-400 mb-1">Hasta</label>
                            <input type="date" name="hasta" value="{{ $endDate }}"
                                   class="rounded-xl border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-[#4a5d41]/20 focus:border-[#4a5d41] px-3 py-2 text-sm">
                        </div>

                        {{-- Quick presets --}}
                        <div class="flex gap-1.5 self-end pb-0.5">
                            @php
                                $presets = [
                                    '7 días'  => [now()->subDays(6)->toDateString(), now()->toDateString()],
                                    '30 días' => [now()->subMonth()->toDateString(), now()->toDateString()],
                                    '90 días' => [now()->subMonths(3)->toDateString(), now()->toDateString()],
                                    'Este mes' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
                                ];
                            @endphp
                            @foreach($presets as $label => $range)
                                <a href="{{ route('admin.reportes.ocupacion', ['desde' => $range[0], 'hasta' => $range[1]]) }}"
                                   class="px-2.5 py-1.5 text-[11px] font-bold rounded-xl border border-zinc-200 dark:border-zinc-700 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors {{ $startDate === $range[0] && $endDate === $range[1] ? 'bg-[#4a5d41] text-white border-[#4a5d41] hover:bg-[#3d4d35]' : '' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>

                        <button type="submit"
                                class="px-5 py-2 bg-[#4a5d41] text-white rounded-xl font-bold text-sm hover:bg-[#3d4d35] transition-colors shadow-sm self-end">
                            Filtrar
                        </button>
                    </form>
                </div>
            </div>

            {{-- Chart area --}}
            <div class="p-8">
                <div class="relative" style="height: 420px;">
                    <canvas id="occupancyChart"></canvas>
                </div>

                {{-- Legend inline --}}
                <div class="flex flex-wrap items-center gap-6 mt-6 text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                    <span class="flex items-center gap-2">
                        <span class="size-3 rounded-sm bg-[#4a5d41]"></span>
                        Baja (&le;50%)
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="size-3 rounded-sm bg-[#f59e0b]"></span>
                        Media (51-80%)
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="size-3 rounded-sm bg-[#ef4444]"></span>
                        Alta (&gt;80%)
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="size-3 rounded-sm bg-[#4a5d41] opacity-30 border border-dashed border-zinc-300"></span>
                        Promedio del período
                    </span>
                </div>
            </div>

            {{-- Calendar Heatmap --}}
            <div class="border-t border-zinc-100 dark:border-zinc-800">
                <div class="px-8 py-5 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-300">Vista rápida por día</h3>
                    <span class="text-xs text-zinc-400">{{ count($data) }} días</span>
                </div>
                <div class="px-8 pb-8">
                    @php
                        $grouped = collect($data)->groupBy(function ($d) {
                            return \Carbon\Carbon::parse($d['fecha'])->format('Y-m');
                        });
                    @endphp

                    <div class="flex flex-wrap gap-6">
                        @foreach($grouped as $mes => $dias)
                            <div>
                                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-2">
                                    {{ \Carbon\Carbon::parse($mes . '-01')->translatedFormat('F Y') }}
                                </p>
                                <div class="flex flex-wrap gap-1.5" style="max-width: 224px;">
                                    @foreach($dias as $d)
                                        @php
                                            $fecha = \Carbon\Carbon::parse($d['fecha']);
                                            $tooltip = $fecha->format('d/m') . ' — ' . $d['ocupadas'] . '/' . $d['total'] . ' (' . $d['porcentaje'] . '%)';
                                        @endphp
                                        <div class="group relative">
                                            <div class="size-7 rounded-lg {{ $d['porcentaje'] > 80 ? 'bg-red-400' : ($d['porcentaje'] > 50 ? 'bg-amber-400' : ($d['porcentaje'] > 0 ? 'bg-emerald-400' : 'bg-zinc-100 dark:bg-zinc-800')) }} flex items-center justify-center cursor-default transition-transform hover:scale-125">
                                                <span class="text-[10px] font-bold {{ $d['porcentaje'] > 0 ? 'text-white' : 'text-zinc-300 dark:text-zinc-600' }}">{{ $fecha->format('j') }}</span>
                                            </div>
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 px-2 py-1 rounded-lg bg-zinc-800 text-white text-[10px] font-semibold whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 shadow-lg">
                                                {{ $tooltip }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Mini legend --}}
                    <div class="flex items-center gap-4 mt-5 text-[11px] font-semibold text-zinc-400">
                        <span class="flex items-center gap-1.5">
                            <span class="size-3 rounded bg-zinc-100 dark:bg-zinc-800"></span>
                            0%
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="size-3 rounded bg-emerald-400"></span>
                            1-50%
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="size-3 rounded bg-amber-400"></span>
                            51-80%
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="size-3 rounded bg-red-400"></span>
                            >80%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('occupancyChart').getContext('2d');

            const labels = @json(array_column($data, 'fecha'));
            const values = @json(array_column($data, 'porcentaje'));
            const average = {{ $promedio }};

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? '#27272a' : '#f4f4f5';
            const textColor = isDark ? '#a1a1aa' : '#71717a';

            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(74, 93, 65, 0.4)');
            gradient.addColorStop(0.5, 'rgba(74, 93, 65, 0.15)');
            gradient.addColorStop(1, 'rgba(74, 93, 65, 0.01)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels.map(d => {
                        const [y, m, day] = d.split('-');
                        return `${day}/${m}`;
                    }),
                    datasets: [
                        {
                            label: 'Ocupación',
                            data: values,
                            backgroundColor: values.map(v =>
                                v > 80 ? '#ef4444' : v > 50 ? '#f59e0b' : '#4a5d41'
                            ),
                            borderRadius: 4,
                            borderSkipped: false,
                            barPercentage: 0.7,
                            categoryPercentage: 0.9,
                            order: 2,
                        },
                        {
                            label: 'Tendencia',
                            data: values,
                            type: 'line',
                            borderColor: '#4a5d41',
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#4a5d41',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            borderWidth: 2.5,
                            order: 1,
                        },
                        {
                            label: 'Promedio',
                            data: values.map(() => average),
                            type: 'line',
                            borderColor: '#4a5d41',
                            borderDash: [6, 4],
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false,
                            order: 0,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 800,
                        easing: 'easeInOutQuart'
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: isDark ? '#18181b' : '#fff',
                            titleColor: isDark ? '#fff' : '#18181b',
                            bodyColor: isDark ? '#a1a1aa' : '#71717a',
                            borderColor: isDark ? '#27272a' : '#e4e4e7',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 12,
                            titleFont: { weight: '700', size: 13 },
                            bodyFont: { size: 13 },
                            displayColors: true,
                            boxPadding: 4,
                            callbacks: {
                                label: function (ctx) {
                                    if (ctx.dataset.label === 'Promedio') return 'Promedio: ' + average + '%';
                                    return ctx.dataset.label + ': ' + ctx.parsed.y + '%';
                                },
                                afterBody: function (items) {
                                    const idx = items[0].dataIndex;
                                    const ocupadas = @json(array_column($data, 'ocupadas'))[idx];
                                    const total = @json(array_column($data, 'total'))[idx];
                                    const libres = total - ocupadas;
                                    return 'Ocupadas: ' + ocupadas + ' / ' + total + ' · Libres: ' + libres;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function (v) { return v + '%'; },
                                color: textColor,
                                font: { weight: '600', size: 11 },
                                stepSize: 25,
                            },
                            grid: {
                                color: gridColor,
                                drawBorder: false,
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: textColor,
                                font: { size: 11, weight: '600' },
                                maxRotation: 45,
                                minRotation: 0,
                                maxTicksLimit: 20,
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-layouts.app>
