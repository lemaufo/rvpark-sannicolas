<?php

use Livewire\Volt\Component;
use App\Models\Unit;

new class extends Component {
    public $type = 'bungalow';
    public $name = '';
    public $notes = '';
    public $recentUnits = [];

    public function mount()
    {
        $this->loadRecent();
    }

    public function loadRecent()
    {
        $this->recentUnits = Unit::latest()->take(5)->get()->toArray();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:60',
            'type' => 'required|in:bungalow,rv,camping',
        ]);

        Unit::create([
            'name'   => $this->name,
            'type'   => $this->type,
            'status' => 'available',
            'notes'  => $this->notes,
        ]);

        $this->reset(['name', 'notes']);
        $this->loadRecent();
    }

    public function counts()
    {
        return [
            'bungalow' => Unit::where('type', 'bungalow')->count(),
            'rv'       => Unit::where('type', 'rv')->count(),
            'camping'  => Unit::where('type', 'camping')->count(),
            'total'    => Unit::count(),
        ];
    }
}; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    {{-- Formulario --}}
    <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl p-8 shadow-sm">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">Nueva Unidad</h2>

        {{-- Tipo --}}
        <div class="mb-6">
            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-3">Tipo de Unidad</p>
            <div class="grid grid-cols-3 gap-3">
                @foreach(['bungalow' => ['label' => 'Bungalow', 'icon' => 'home'], 'rv' => ['label' => 'RV Spot', 'icon' => 'bolt'], 'camping' => ['label' => 'Camping', 'icon' => 'map-pin']] as $key => $item)
                    <button type="button" wire:click="$set('type', '{{ $key }}')"
                        class="flex flex-col items-center gap-2 p-4 rounded-2xl border-2 transition
                            {{ $type === $key ? 'border-[#4a5d41] bg-[#4a5d41]/5 text-[#4a5d41]' : 'border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:border-zinc-300' }}">
                        <flux:icon name="{{ $item['icon'] }}" class="size-7" />
                        <span class="text-sm font-bold">{{ $item['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Nombre --}}
        <div class="mb-5">
            <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Nombre de la Unidad *</label>
            <flux:input wire:model="name" placeholder="ej. Jade, Coral, Amber" />
        </div>

        {{-- Notas --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Especificaciones</label>
            <flux:textarea wire:model="notes" placeholder="ej. 2 camas, AC, Cocina, Vista al lago..." rows="3" />
        </div>

        <button wire:click="save" type="button"
            class="w-full py-3 bg-[#4a5d41] hover:bg-[#3d4d35] text-white font-bold rounded-xl transition-colors">
            Agregar Unidad
        </button>
    </div>

    {{-- Panel derecho --}}
    <div class="space-y-6">
        {{-- Agregadas recientemente --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl p-6 shadow-sm">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white mb-4">Agregadas Recientemente</h3>
            @forelse($recentUnits as $u)
                <div class="flex items-center gap-3 py-2 border-b border-zinc-50 dark:border-zinc-800 last:border-0">
                    <flux:icon name="home" class="size-5 text-zinc-400" />
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-white">{{ $u['name'] }}</p>
                        <p class="text-xs text-zinc-400">{{ ucfirst($u['type']) }}</p>
                    </div>
                </div>
            @empty
                <div class="text-center py-6">
                    <flux:icon name="home" class="size-10 text-zinc-200 mx-auto mb-2" />
                    <p class="text-sm text-zinc-400">No hay unidades agregadas aún</p>
                </div>
            @endforelse
        </div>

        {{-- Inventario actual --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl p-6 shadow-sm">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white mb-4">Inventario Actual</h3>
            @php $counts = $this->counts(); @endphp
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <flux:icon name="home" class="size-4" /> Bungalows
                    </div>
                    <span class="bg-[#4a5d41] text-white text-xs font-bold px-2.5 py-1 rounded-full">{{ $counts['bungalow'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <flux:icon name="bolt" class="size-4" /> RV Spots
                    </div>
                    <span class="bg-[#4a5d41] text-white text-xs font-bold px-2.5 py-1 rounded-full">{{ $counts['rv'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <flux:icon name="map-pin" class="size-4" /> Áreas Camping
                    </div>
                    <span class="bg-[#4a5d41] text-white text-xs font-bold px-2.5 py-1 rounded-full">{{ $counts['camping'] }}</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-800">
                    <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">Total</span>
                    <span class="bg-zinc-800 dark:bg-zinc-200 dark:text-zinc-900 text-white text-xs font-bold px-2.5 py-1 rounded-full">{{ $counts['total'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>