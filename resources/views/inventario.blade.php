<x-layouts.admin active="inventario">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-zinc-900 tracking-tight">Gestión de Inventario</h1>
        <p class="text-zinc-500 mt-1 font-medium italic">Ver y administrar todas las unidades</p>
    </div>

    <!-- Filters / Tabs -->
    <div class="flex flex-wrap gap-2 mb-10">
        <flux:button variant="primary" size="sm" class="rounded-full px-5 bg-zinc-800">Todas (28)</flux:button>
        <flux:button variant="ghost" size="sm" class="rounded-full px-5 text-zinc-600 hover:bg-zinc-100 font-bold">Bungalows (10)</flux:button>
        <flux:button variant="ghost" size="sm" class="rounded-full px-5 text-zinc-600 hover:bg-zinc-100 font-bold">RV Spots (12)</flux:button>
        <flux:button variant="ghost" size="sm" class="rounded-full px-5 text-zinc-600 hover:bg-zinc-100 font-bold">Camping (6)</flux:button>
    </div>

    <!-- Inventory Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        @php
            $units = [
                ['name' => 'Jade', 'type' => 'Bungalow', 'status' => 'Ocupado', 'status_color' => 'red', 'guest' => 'Familia Smith', 'details' => '2 camas, AC, Cocina'],
                ['name' => 'Coral', 'type' => 'Bungalow', 'status' => 'Disponible', 'status_color' => 'green', 'guest' => null, 'details' => '2 camas, AC, Cocina'],
                ['name' => 'Amber', 'type' => 'Bungalow', 'status' => 'Limpieza', 'status_color' => 'orange', 'guest' => null, 'details' => '1 cama, AC, Cocineta'],
                ['name' => 'Pearl', 'type' => 'Bungalow', 'status' => 'Ocupado', 'status_color' => 'red', 'guest' => 'Martínez, A.', 'details' => '3 camas, AC, Cocina Completa'],
                ['name' => 'Spot 01', 'type' => 'RV Spot', 'status' => 'Disponible', 'status_color' => 'green', 'guest' => null, 'details' => '30/50 Amp, Agua, Desagüe'],
                ['name' => 'Spot 02', 'type' => 'RV Spot', 'status' => 'Ocupado', 'status_color' => 'red', 'guest' => 'Wilson, J.', 'details' => '30 Amp, Agua'],
                ['name' => 'Camp A1', 'type' => 'Camping', 'status' => 'Disponible', 'status_color' => 'green', 'guest' => null, 'details' => 'Área sombreada, Mesa'],
                ['name' => 'Camp A2', 'type' => 'Camping', 'status' => 'Disponible', 'status_color' => 'green', 'guest' => null, 'details' => 'Cerca de baños'],
            ];
        @endphp

        @foreach($units as $unit)
            <div class="bg-white border border-zinc-200 rounded-[2rem] p-6 shadow-sm hover:shadow-md transition-shadow duration-300">
                <!-- Thumbnail Area (Landscape) -->
                <div class="relative aspect-[16/11] w-full bg-[#EBE7E0] rounded-[1.5rem] flex items-center justify-center mb-6 overflow-hidden">
                    <flux:icon name="home" class="size-20 text-[#C9C5BE]" variant="outline" />
                    
                    <!-- Status Dot -->
                    <div class="absolute top-4 right-4 size-3.5 rounded-full ring-4 ring-[#EBE7E0]
                        {{ $unit['status_color'] === 'red' ? 'bg-[#E35F5F]' : '' }}
                        {{ $unit['status_color'] === 'green' ? 'bg-[#5EB37E]' : '' }}
                        {{ $unit['status_color'] === 'orange' ? 'bg-[#E3A65F]' : '' }}
                    "></div>
                </div>

                <!-- Info Area -->
                <div>
                    <h3 class="text-2xl font-bold text-zinc-900 leading-tight mb-0.5">{{ $unit['name'] }}</h3>
                    <p class="text-sm text-zinc-400 mb-4">{{ $unit['type'] }}</p>

                    <!-- Status Badge -->
                    <div class="mb-5">
                        <span class="inline-flex items-center px-4 py-1 rounded-full text-xs font-bold border-none
                            {{ $unit['status_color'] === 'red' ? 'bg-red-50 text-[#D85C5C]' : '' }}
                            {{ $unit['status_color'] === 'green' ? 'bg-emerald-50 text-[#4E9F6B]' : '' }}
                            {{ $unit['status_color'] === 'orange' ? 'bg-amber-50 text-[#D8934E]' : '' }}
                        ">
                            {{ $unit['status'] }}
                        </span>
                    </div>

                    <div class="pt-5 border-t border-zinc-100 space-y-1">
                        @if($unit['guest'])
                            <p class="text-sm">
                                <span class="text-zinc-400">Huésped:</span><br>
                                <span class="text-zinc-900 font-bold text-base">{{ $unit['guest'] }}</span>
                            </p>
                        @endif
                        <p class="text-sm text-zinc-500 font-medium">
                            {{ $unit['details'] }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.admin>
