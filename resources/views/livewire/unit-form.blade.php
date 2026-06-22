<?php

use Livewire\Volt\Component;
use App\Models\Unit;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public $type = 'bungalow';
    public $name = '';
    public $notes = '';
    public $price_per_day = 0;
    public $price_per_hour = 0;
    public $image;
    public $recentUnits = [];
    public $editingUnit = null;
    public $showEditModal = false;

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
            'price_per_day' => 'required|numeric|min:0',
            'price_per_hour' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name'   => $this->name,
            'type'   => $this->type,
            'status' => 'available',
            'notes'  => $this->notes,
            'price_per_day' => $this->price_per_day,
            'price_per_hour' => $this->price_per_hour,
        ];

        if ($this->image) {
            $data['image'] = $this->image->store('units', 'public');
        }

        Unit::create($data);

        $this->reset(['name', 'notes', 'price_per_day', 'price_per_hour', 'image']);
        $this->loadRecent();
    }

    public function editUnit($id)
    {
        $unit = Unit::find($id);
        $this->editingUnit = $unit;
        $this->name = $unit->name;
        $this->type = $unit->type;
        $this->notes = $unit->notes;
        $this->price_per_day = $unit->price_per_day;
        $this->price_per_hour = $unit->price_per_hour;
        $this->showEditModal = true;
    }

    public function updateUnit()
    {
        $this->validate([
            'name' => 'required|string|max:60',
            'type' => 'required|in:bungalow,rv,camping',
            'price_per_day' => 'required|numeric|min:0',
            'price_per_hour' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name'   => $this->name,
            'type'   => $this->type,
            'notes'  => $this->notes,
            'price_per_day' => $this->price_per_day,
            'price_per_hour' => $this->price_per_hour,
        ];

        if ($this->image) {
            $data['image'] = $this->image->store('units', 'public');
        }

        $this->editingUnit->update($data);

        $this->reset(['name', 'notes', 'price_per_day', 'price_per_hour', 'image', 'editingUnit', 'showEditModal']);
        $this->loadRecent();
    }

    public function deleteUnit($id)
    {
        Unit::findOrFail($id)->delete();
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
        <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">{{ $editingUnit ? 'Editar Unidad' : 'Nueva Unidad' }}</h2>

        {{-- Tipo --}}
        <div class="mb-6">
            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-3">Tipo de Unidad</p>
            <div class="grid grid-cols-3 gap-2 sm:gap-3">
                @foreach(['bungalow' => ['label' => 'Bungalow', 'icon' => 'home'], 'rv' => ['label' => 'RV Spot', 'icon' => 'bolt'], 'camping' => ['label' => 'Camping', 'icon' => 'map-pin']] as $key => $item)
                    <button type="button" wire:click="$set('type', '{{ $key }}')"
                        class="flex flex-col items-center justify-center gap-1.5 sm:gap-2 p-2 sm:p-4 rounded-xl sm:rounded-2xl border-2 transition
                            {{ $type === $key ? 'border-[#4a5d41] bg-[#4a5d41]/5 text-[#4a5d41]' : 'border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:border-zinc-300' }}">
                        <flux:icon name="{{ $item['icon'] }}" class="size-6 sm:size-7 shrink-0" />
                        <span class="text-[11px] sm:text-sm font-bold truncate w-full text-center leading-tight">{{ $item['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Nombre --}}
        <div class="mb-5">
            <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Nombre de la Unidad *</label>
            <flux:input wire:model="name" placeholder="ej. Jade, Coral, Amber" />
        </div>

        {{-- Precios --}}
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div>
                <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Precio por Día *</label>
                <flux:input wire:model="price_per_day" type="number" step="0.01" min="0" placeholder="0.00" />
            </div>
            <div>
                <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Precio por Hora *</label>
                <flux:input wire:model="price_per_hour" type="number" step="0.01" min="0" placeholder="0.00" />
            </div>
        </div>

        {{-- Imagen --}}
        <div class="mb-5">
            <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Imagen de la Unidad</label>
            <input type="file" wire:model="image" accept="image/*"
                class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-[#4a5d41] file:text-white hover:file:bg-[#3d4d35] file:cursor-pointer" />
            @if($image)
                <div class="mt-3">
                    <img src="{{ $image->temporaryUrl() }}" class="h-20 rounded-lg object-cover" />
                </div>
            @endif
        </div>

        {{-- Notas --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Especificaciones</label>
            <flux:textarea wire:model="notes" placeholder="ej. 2 camas, AC, Cocina, Vista al lago..." rows="3" />
        </div>

        <div class="flex gap-3">
            <button wire:click="{{ $editingUnit ? 'updateUnit' : 'save' }}" type="button"
                class="flex-1 py-3 bg-[#4a5d41] hover:bg-[#3d4d35] text-white font-bold rounded-xl transition-colors">
                {{ $editingUnit ? 'Actualizar Unidad' : 'Agregar Unidad' }}
            </button>
            @if($editingUnit)
                <button wire:click="$set('editingUnit', null); $set('showEditModal', false); $reset(['name', 'notes', 'price_per_day', 'price_per_hour', 'image'])" type="button"
                    class="px-6 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors">
                    Cancelar
                </button>
            @endif
        </div>
    </div>

    {{-- Panel derecho --}}
    <div class="space-y-6">
        {{-- Agregadas recientemente --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl p-6 shadow-sm">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white mb-4">Agregadas Recientemente</h3>
            @forelse($recentUnits as $u)
                <div class="flex items-center gap-3 py-2 border-b border-zinc-50 dark:border-zinc-800 last:border-0">
                    @if($u['image'])
                        <img src="{{ asset('storage/' . $u['image']) }}" class="size-8 rounded-lg object-cover" />
                    @else
                        <flux:icon name="home" class="size-5 text-zinc-400" />
                    @endif
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-zinc-800 dark:text-white">{{ $u['name'] }}</p>
                        <p class="text-xs text-zinc-400">{{ ucfirst($u['type']) }} - ${{ number_format($u['price_per_day'], 2) }}/día</p>
                    </div>
                    <div class="flex gap-1">
                        <button wire:click="editUnit({{ $u['id'] }}" class="p-1.5 text-zinc-400 hover:text-blue-500 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                            <flux:icon name="pencil" class="size-4" />
                        </button>
                        <button wire:click="deleteUnit({{ $u['id'] }})" wire:confirm="¿Eliminar esta unidad?" class="p-1.5 text-zinc-400 hover:text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            <flux:icon name="trash" class="size-4" />
                        </button>
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