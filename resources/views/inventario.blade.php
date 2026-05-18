<x-layouts.app>

    <div x-data="{
        filtro: 'all',
        modalAbierto: false,
        unidad: {},
        abrirModal(unit) {
            this.unidad = unit;
            this.modalAbierto = true;
        }
    }" class="p-6 lg:p-10 max-w-7xl mx-auto">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                Gestión de Inventario
            </h1>

            <p class="text-zinc-500 dark:text-zinc-400 mt-1 font-medium italic">
                Ver y administrar todas las unidades
            </p>
        </div>

        @php
            $units = \App\Models\Unit::all()
                ->map(function ($unit) {
                    return [
                        'name' => $unit->name,
                        'type' => $unit->type,
                        'type_label' => match ($unit->type) {
                            'rv' => 'RV Spot',
                            'camping' => 'Camping',
                            default => 'Bungalow',
                        },
                        'status' => $unit->status,
                        'status_label' => match ($unit->status) {
                            'available' => 'Disponible',
                            'occupied' => 'Ocupado',
                            'cleaning' => 'Limpieza',
                            default => $unit->status,
                        },
                        'status_color' => match ($unit->status) {
                            'available' => 'green',
                            'occupied' => 'red',
                            'cleaning' => 'orange',
                            default => 'green',
                        },
                        'guest' => null,
                        'checkin' => null,
                        'checkout' => null,
                        'details' => $unit->notes ?? 'Sin detalles adicionales',
                    ];
                })
                ->toArray();
        @endphp

        {{-- Filtros --}}
        <div class="flex flex-wrap gap-2 mb-10">

            <button @click="filtro = 'all'"
                :class="filtro === 'all'
                    ?
                    'bg-[#556B46] text-white' :
                    'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                class="rounded-full px-5 py-1.5 text-sm font-bold border border-zinc-200 dark:border-zinc-700 transition">
                Todas ({{ count($units) }})
            </button>

            <button @click="filtro = 'bungalow'"
                :class="filtro === 'bungalow'
                    ?
                    'bg-[#556B46] text-white' :
                    'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                class="rounded-full px-5 py-1.5 text-sm font-bold border border-zinc-200 dark:border-zinc-700 transition">
                Bungalows ({{ count(array_filter($units, fn($u) => $u['type'] === 'bungalow')) }})
            </button>

            <button @click="filtro = 'rv'"
                :class="filtro === 'rv'
                    ?
                    'bg-[#556B46] text-white' :
                    'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                class="rounded-full px-5 py-1.5 text-sm font-bold border border-zinc-200 dark:border-zinc-700 transition">
                RV Spots ({{ count(array_filter($units, fn($u) => $u['type'] === 'rv')) }})
            </button>

            <button @click="filtro = 'camping'"
                :class="filtro === 'camping'
                    ?
                    'bg-[#556B46] text-white' :
                    'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                class="rounded-full px-5 py-1.5 text-sm font-bold border border-zinc-200 dark:border-zinc-700 transition">
                Camping ({{ count(array_filter($units, fn($u) => $u['type'] === 'camping')) }})
            </button>

        </div>

        {{-- Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 items-stretch">

            @foreach ($units as $unit)
                <div x-show="filtro === 'all' || filtro === '{{ $unit['type'] }}'" x-transition
                    @click="abrirModal({{ json_encode($unit) }})"
                    class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-[2rem] p-6 shadow-sm hover:shadow-md transition-shadow duration-300 cursor-pointer h-full">

                    {{-- Imagen --}}
                    <div
                        class="relative aspect-[16/11] w-full bg-[#EBE7E0] dark:bg-zinc-800 rounded-[1.5rem] flex items-center justify-center mb-6 overflow-hidden">

                        <flux:icon name="home" class="size-20 text-[#C9C5BE] dark:text-zinc-600" variant="outline" />

                        <div
                            class="absolute top-4 right-4 size-3.5 rounded-full ring-4 ring-[#EBE7E0] dark:ring-zinc-800
                            {{ $unit['status_color'] === 'red' ? 'bg-[#E35F5F]' : '' }}
                            {{ $unit['status_color'] === 'green' ? 'bg-[#5EB37E]' : '' }}
                            {{ $unit['status_color'] === 'orange' ? 'bg-[#E3A65F]' : '' }}">
                        </div>

                    </div>

                    {{-- Info --}}
                    <div>

                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white leading-tight mb-0.5">
                            {{ $unit['name'] }}
                        </h3>

                        <p class="text-sm text-zinc-400 dark:text-zinc-500 mb-4">
                            {{ $unit['type_label'] }}
                        </p>

                        <div class="mb-5">
                            <span
                                class="inline-flex items-center px-4 py-1 rounded-full text-xs font-bold
                                {{ $unit['status_color'] === 'red' ? 'bg-red-50 text-[#D85C5C]' : '' }}
                                {{ $unit['status_color'] === 'green' ? 'bg-emerald-50 text-[#4E9F6B]' : '' }}
                                {{ $unit['status_color'] === 'orange' ? 'bg-amber-50 text-[#D8934E]' : '' }}">
                                {{ $unit['status_label'] }}
                            </span>
                        </div>

                        <div class="pt-5 border-t border-zinc-100 dark:border-zinc-800 space-y-1">

                            @if ($unit['guest'])
                                <p class="text-sm">
                                    <span class="text-zinc-400 dark:text-zinc-500">
                                        Huésped:
                                    </span><br>

                                    <span class="text-zinc-900 dark:text-white font-bold text-base">
                                        {{ $unit['guest'] }}
                                    </span>
                                </p>
                            @endif

                            <p class="text-sm text-zinc-500 dark:text-zinc-400 font-medium">
                                {{ $unit['details'] }}
                            </p>

                        </div>

                    </div>

                </div>
            @endforeach

        </div>

        {{-- Modal --}}
        <div x-show="modalAbierto" x-transition.opacity @click.self="modalAbierto = false"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none">

            <div
                class="bg-white dark:bg-zinc-900 rounded-[2rem] w-full max-w-md mx-4 overflow-hidden shadow-xl border border-zinc-200 dark:border-zinc-800">

                {{-- Imagen --}}
                <div class="relative aspect-[16/7] bg-[#EBE7E0] dark:bg-zinc-800 flex items-center justify-center">

                    <flux:icon name="home" class="size-24 text-[#C9C5BE] dark:text-zinc-600" variant="outline" />

                    <div class="absolute top-4 right-4 size-4 rounded-full ring-4 ring-[#EBE7E0] dark:ring-zinc-800"
                        :class="{
                            'bg-[#E35F5F]': unidad.status_color === 'red',
                            'bg-[#5EB37E]': unidad.status_color === 'green',
                            'bg-[#E3A65F]': unidad.status_color === 'orange'
                        }">
                    </div>

                    <button @click="modalAbierto = false"
                        class="absolute top-3 right-3 bg-white dark:bg-zinc-800 rounded-full w-8 h-8 flex items-center justify-center text-zinc-500 hover:text-zinc-800 dark:hover:text-white">
                        ✕
                    </button>

                </div>

                <div class="p-6">

                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-4" x-text="unidad.name">
                    </h2>

                    {{-- Tipo y Estado --}}
                    <div class="grid grid-cols-2 gap-4 mb-4">

                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                Tipo
                            </p>

                            <p class="font-bold text-zinc-900 dark:text-white" x-text="unidad.type_label">
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                Estado
                            </p>

                            <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold"
                                :class="{
                                    'bg-red-50 text-[#D85C5C]': unidad.status_color === 'red',
                                    'bg-emerald-50 text-[#4E9F6B]': unidad.status_color === 'green',
                                    'bg-amber-50 text-[#D8934E]': unidad.status_color === 'orange'
                                }"
                                x-text="unidad.status_label">
                            </span>
                        </div>

                    </div>

                    {{-- Especificaciones --}}
                    <div class="mb-4">

                        <p class="text-xs text-zinc-400 dark:text-zinc-500">
                            Especificaciones
                        </p>

                        <p class="text-zinc-700 dark:text-zinc-300 font-medium" x-text="unidad.details">
                        </p>

                    </div>

                    {{-- Huésped --}}
                    <template x-if="unidad.status === 'occupied'">

                        <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4 mb-4">

                            <div class="flex items-center gap-2 mb-2">

                                <flux:icon name="user" class="size-4 text-zinc-400" variant="outline" />

                                <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                    Huésped Actual
                                </p>

                            </div>

                            <p class="font-bold text-zinc-900 dark:text-white text-base mb-3" x-text="unidad.guest">
                            </p>

                            <div class="grid grid-cols-2 gap-4">

                                <div>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                        Entrada
                                    </p>

                                    <p class="font-medium text-zinc-800 dark:text-zinc-300" x-text="unidad.checkin">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                        Salida
                                    </p>

                                    <p class="font-medium text-zinc-800 dark:text-zinc-300" x-text="unidad.checkout">
                                    </p>
                                </div>

                            </div>

                        </div>

                    </template>

                    {{-- Botones --}}
                    <div class="flex gap-3 mt-2">

                        <template x-if="unidad.status === 'occupied'">
                            <button
                                class="flex-1 bg-[#4a6741] hover:bg-[#3d5636] text-white py-3 rounded-xl font-bold text-sm">
                                Registrar Salida
                            </button>
                        </template>

                        <template x-if="unidad.status === 'available'">
                            <button
                                class="flex-1 bg-[#4a6741] hover:bg-[#3d5636] text-white py-3 rounded-xl font-bold text-sm">
                                Nueva Reservación
                            </button>
                        </template>

                        <template x-if="unidad.status === 'cleaning'">
                            <button
                                class="flex-1 bg-[#4a6741] hover:bg-[#3d5636] text-white py-3 rounded-xl font-bold text-sm">
                                Marcar como Limpio
                            </button>
                        </template>

                        <button @click="modalAbierto = false"
                            class="px-4 py-2.5 text-sm text-[#6b7566] bg-[#f0ede8] dark:bg-zinc-800 dark:text-zinc-300 hover:bg-[#e5e3df] dark:hover:bg-zinc-700 rounded-lg transition-colors">
                            Cerrar
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-layouts.app>
