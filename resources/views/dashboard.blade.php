<x-layouts.admin active="dashboard">
    <div class="mb-10 flex items-end justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-zinc-900 tracking-tight">Panel Principal</h1>
            <p class="text-zinc-500 mt-1 font-medium italic">Resumen para {{ now()->translatedFormat('l, d \d\e F Y') }}</p>
        </div>
        <button class="bg-[#4a5d41] text-white px-6 py-3 rounded-2xl font-bold shadow-xl shadow-brand-green/20 hover:scale-[1.02] transition-all duration-200 flex items-center gap-2.5">
            <flux:icon name="plus" class="size-5" />
            <span>Nueva Reserva</span>
        </button>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        @php
            $stats = [
                ['label' => 'Ocupación Hoy', 'value' => '82%', 'change' => '+5%', 'icon' => 'home', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-50'],
                ['label' => 'Unidades Disponibles', 'value' => '5', 'sub' => 'de 28', 'icon' => 'users', 'color' => 'text-zinc-400', 'bg' => 'bg-zinc-50'],
                ['label' => 'Entradas Hoy', 'value' => '3', 'sub' => '2 pendientes', 'icon' => 'arrow-right-start-on-rectangle', 'color' => 'text-zinc-400', 'bg' => 'bg-zinc-50'],
                ['label' => 'Ingresos Diarios', 'value' => '$2,450', 'change' => '+12%', 'icon' => 'currency-dollar', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-50'],
            ];
        @endphp

        @foreach($stats as $stat)
            <div class="bg-white border border-zinc-100 rounded-[2rem] p-7 shadow-sm hover:shadow-md transition-shadow duration-300 group">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-zinc-400 text-[13px] font-bold uppercase tracking-wider">{{ $stat['label'] }}</p>
                        <h3 class="text-3xl font-black text-zinc-900 mt-2">{{ $stat['value'] }}</h3>
                        @isset($stat['change'])
                            <p class="{{ $stat['color'] }} text-xs font-bold mt-1.5 flex items-center gap-1">
                                <span>{{ $stat['change'] }}</span>
                            </p>
                        @endisset
                        @isset($stat['sub'])
                            <p class="text-zinc-400 text-xs font-bold mt-1.5">{{ $stat['sub'] }}</p>
                        @endisset
                    </div>
                    <div class="p-4 {{ $stat['bg'] }} rounded-2xl group-hover:scale-110 transition-transform duration-300">
                        <flux:icon :name="$stat['icon']" class="size-7 text-zinc-800" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Main Content (2/3) -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white border border-zinc-100 rounded-[2.5rem] p-8 shadow-sm">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-2xl font-black text-zinc-900 tracking-tight">Ocupación en Vivo</h2>
                    <a href="#" class="text-[#4a5d41] text-sm font-bold hover:underline underline-offset-4 flex items-center gap-1">
                        Ver Todas 
                        <flux:icon name="chevron-right" class="size-4" />
                    </a>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Bungalow Card -->
                    <div class="p-6 border border-zinc-50 rounded-3xl bg-[#fcfcfc] flex justify-between items-start group hover:border-brand-green/20 transition-colors">
                        <div>
                            <h4 class="font-extrabold text-zinc-900 text-lg">Bungalow Jade</h4>
                            <p class="text-xs text-zinc-400 font-bold uppercase tracking-widest mt-0.5">Bungalow</p>
                            <span class="inline-block mt-4 px-3 py-1.5 bg-red-50 text-red-600 text-[10px] font-black rounded-xl uppercase tracking-widest">Ocupado</span>
                            <div class="flex items-center gap-2 mt-4">
                                <div class="size-6 rounded-full bg-zinc-200 border-2 border-white"></div>
                                <p class="text-xs text-zinc-600 font-bold">Familia Smith</p>
                            </div>
                        </div>
                        <div class="size-3 rounded-full bg-red-500 shadow-lg shadow-red-200"></div>
                    </div>

                    <!-- Bungalow Card -->
                    <div class="p-6 border border-zinc-50 rounded-3xl bg-[#fcfcfc] flex justify-between items-start group hover:border-brand-green/20 transition-colors">
                        <div>
                            <h4 class="font-extrabold text-zinc-900 text-lg">Bungalow Coral</h4>
                            <p class="text-xs text-zinc-400 font-bold uppercase tracking-widest mt-0.5">Bungalow</p>
                            <span class="inline-block mt-4 px-3 py-1.5 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-xl uppercase tracking-widest">Disponible</span>
                            <p class="text-xs text-zinc-400 font-bold mt-4 italic">Listo para check-in</p>
                        </div>
                        <div class="size-3 rounded-full bg-emerald-500 shadow-lg shadow-emerald-200"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Notifications (1/3) -->
        <div class="space-y-8">
            <div class="bg-white border border-zinc-100 rounded-[2.5rem] p-8 shadow-sm">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-zinc-50 rounded-xl">
                            <flux:icon name="bell" class="size-5 text-zinc-800" />
                        </div>
                        <h2 class="text-xl font-black text-zinc-900 tracking-tight">Notificaciones</h2>
                    </div>
                    <span class="size-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">2</span>
                </div>
                
                <div class="space-y-6 relative before:absolute before:left-[19px] before:top-2 before:bottom-2 before:w-px before:bg-zinc-100">
                    <div class="flex gap-5 relative group">
                        <div class="size-10 rounded-2xl bg-white border border-zinc-100 flex items-center justify-center shrink-0 z-10 group-hover:border-brand-green/30 transition-colors shadow-sm">
                            <flux:icon name="exclamation-circle" class="size-5 text-zinc-400" />
                        </div>
                        <div class="pt-1">
                            <p class="text-[13px] font-bold text-zinc-800 leading-snug">Recordatorio de salida: RV Spot 8</p>
                            <p class="text-[11px] text-zinc-400 font-bold mt-1 uppercase tracking-wider">Hace 1 hora</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-5 relative group">
                        <div class="size-10 rounded-2xl bg-white border border-zinc-100 flex items-center justify-center shrink-0 z-10 group-hover:border-brand-green/30 transition-colors shadow-sm">
                            <flux:icon name="check-circle" class="size-5 text-emerald-500" />
                        </div>
                        <div class="pt-1">
                            <p class="text-[13px] font-bold text-zinc-800 leading-snug">Pago recibido: $450 - Reserva directa</p>
                            <p class="text-[11px] text-zinc-400 font-bold mt-1 uppercase tracking-wider">Hace 2 horas</p>
                        </div>
                    </div>
                </div>

                <button class="w-full mt-8 py-3 bg-zinc-50 text-zinc-500 text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-zinc-100 transition-colors">
                    Ver todo el historial
                </button>
            </div>
        </div>
    </div>
</x-layouts.admin>
