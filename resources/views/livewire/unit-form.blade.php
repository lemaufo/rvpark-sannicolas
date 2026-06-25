<?php

use Livewire\Volt\Component;
use App\Models\Unit;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithFileUploads;
    use WithPagination;

    // Propiedades para crear unidad (Formulario Izquierdo)
    public $type = 'bungalow';
    public $name = '';
    public $notes = '';
    public $price_per_day = '';
    public $price_per_hour = '';
    public $image;

    // Propiedades para editar unidad (Modal)
    public $edit_type = 'bungalow';
    public $edit_name = '';
    public $edit_notes = '';
    public $edit_price_per_day = 0;
    public $edit_price_per_hour = 0;
    public $edit_image;

    public $editingUnit = null;

    public function with()
    {
        $units = Unit::latest()->paginate(8);

        // Una sola consulta para obtener IDs de unidades ocupadas o reservadas (evita N+1)
        $occupiedUnitIds = \App\Models\Reservation::whereIn('status', ['confirmed', 'checked_in'])
            ->pluck('unit_id')
            ->unique()
            ->toArray();

        // Agregar bandera is_occupied a cada unidad para la vista
        $units->getCollection()->transform(function ($unit) use ($occupiedUnitIds) {
            $unit->is_occupied = $unit->status === 'occupied' || in_array($unit->id, $occupiedUnitIds);
            return $unit;
        });

        return [
            'unitsList' => $units,
        ];
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:60',
            'type' => 'required|in:bungalow,rv,camping',
            'price_per_day' => 'required|numeric|min:0',
            'price_per_hour' => 'required|numeric|min:0',
            'notes' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
        ], [
            'name.required' => 'El nombre de la unidad es obligatorio.',
            'name.max' => 'El nombre no debe exceder 60 caracteres.',
            'type.required' => 'El tipo de unidad es obligatorio.',
            'type.in' => 'El tipo debe ser Bungalow, RV o Camping.',
            'price_per_day.required' => 'El precio por día es obligatorio.',
            'price_per_day.numeric' => 'El precio por día debe ser un número.',
            'price_per_day.min' => 'El precio por día no puede ser negativo.',
            'price_per_hour.required' => 'El precio por hora es obligatorio.',
            'price_per_hour.numeric' => 'El precio por hora debe ser un número.',
            'price_per_hour.min' => 'El precio por hora no puede ser negativo.',
            'notes.required' => 'Las especificaciones son obligatorias.',
            'notes.max' => 'Las especificaciones no deben exceder 255 caracteres.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.max' => 'La imagen no debe exceder 2 MB.',
        ]);

        try {
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
            $this->dispatch('unitCreated');
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo crear la unidad. Intenta de nuevo.');
        }
    }

    public function editUnit($id)
    {
        $unit = Unit::find($id);

        // Validar que la unidad no esté ocupada
        if ($this->isUnitOccupied($unit)) {
            $this->dispatch('swal-unit-occupied', ['action' => 'editar', 'unit' => $unit->name]);
            return;
        }

        $this->editingUnit = $unit;
        $this->edit_name = $unit->name;
        $this->edit_type = $unit->type;
        $this->edit_notes = $unit->notes;
        $this->edit_price_per_day = $unit->price_per_day;
        $this->edit_price_per_hour = $unit->price_per_hour;
        $this->edit_image = null;
        
        \Flux::modal('edit-unit-modal')->show();
    }

    private function isUnitOccupied($unit): bool
    {
        if (!$unit) return false;

        // Verificar por estado directo 'occupied'
        if ($unit->status === 'occupied') {
            return true;
        }

        // Verificar si hay una reservación activa (confirmed o checked_in)
        return \App\Models\Reservation::where('unit_id', $unit->id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->exists();
    }

    public function updateUnit()
    {
        // Validación de seguridad: la unidad no debe estar ocupada
        if ($this->editingUnit && $this->isUnitOccupied($this->editingUnit)) {
            $this->dispatch('swal-unit-occupied', ['action' => 'editar', 'unit' => $this->editingUnit->name]);
            \Flux::modal('edit-unit-modal')->close();
            return;
        }

        $this->validate([
            'edit_name' => 'required|string|max:60',
            'edit_type' => 'required|in:bungalow,rv,camping',
            'edit_price_per_day' => 'required|numeric|min:0',
            'edit_price_per_hour' => 'required|numeric|min:0',
            'edit_notes' => 'required|string|max:255',
            'edit_image' => 'nullable|image|max:2048',
        ], [
            'edit_name.required' => 'El nombre de la unidad es obligatorio.',
            'edit_name.max' => 'El nombre no debe exceder 60 caracteres.',
            'edit_type.required' => 'El tipo de unidad es obligatorio.',
            'edit_type.in' => 'El tipo debe ser Bungalow, RV o Camping.',
            'edit_price_per_day.required' => 'El precio por día es obligatorio.',
            'edit_price_per_day.numeric' => 'El precio por día debe ser un número.',
            'edit_price_per_day.min' => 'El precio por día no puede ser negativo.',
            'edit_price_per_hour.required' => 'El precio por hora es obligatorio.',
            'edit_price_per_hour.numeric' => 'El precio por hora debe ser un número.',
            'edit_price_per_hour.min' => 'El precio por hora no puede ser negativo.',
            'edit_notes.required' => 'Las especificaciones son obligatorias.',
            'edit_notes.max' => 'Las especificaciones no deben exceder 255 caracteres.',
            'edit_image.image' => 'El archivo debe ser una imagen.',
            'edit_image.max' => 'La imagen no debe exceder 2 MB.',
        ]);

        try {
            $data = [
                'name'   => $this->edit_name,
                'type'   => $this->edit_type,
                'notes'  => $this->edit_notes,
                'price_per_day' => $this->edit_price_per_day,
                'price_per_hour' => $this->edit_price_per_hour,
            ];

            if ($this->edit_image) {
                $data['image'] = $this->edit_image->store('units', 'public');
            }

            $this->editingUnit->update($data);

            $this->reset(['edit_name', 'edit_notes', 'edit_price_per_day', 'edit_price_per_hour', 'edit_image', 'editingUnit']);
            \Flux::modal('edit-unit-modal')->close();
            $this->dispatch('unitUpdated');
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo actualizar la unidad. Intenta de nuevo.');
        }
    }

    public $unitToDeleteName = '';
    public $unitToDeleteId = null;

    public function confirmDelete($id)
    {
        $unit = Unit::find($id);
        if (!$unit) {
            $this->dispatch('swal-error', 'No se encontró la unidad.');
            return;
        }

        // Validar que la unidad no esté ocupada
        if ($this->isUnitOccupied($unit)) {
            $this->dispatch('swal-unit-occupied', ['action' => 'eliminar', 'unit' => $unit->name]);
            return;
        }

        $this->unitToDeleteId = $id;
        $this->unitToDeleteName = $unit->name;
        $this->dispatch('swal-confirm-delete', $unit->name);
    }

    public function deleteUnit()
    {
        try {
            $unit = Unit::find($this->unitToDeleteId);
            if (!$unit) {
                $this->dispatch('swal-error', 'No se encontró la unidad.');
                return;
            }

            // Validación de seguridad: la unidad no debe estar ocupada
            if ($this->isUnitOccupied($unit)) {
                $this->dispatch('swal-unit-occupied', ['action' => 'eliminar', 'unit' => $unit->name]);
                $this->unitToDeleteId = null;
                $this->unitToDeleteName = '';
                return;
            }

            $unit->delete();
            $this->unitToDeleteId = null;
            $this->unitToDeleteName = '';
            $this->unitsList = Unit::latest()->paginate(8);
            $this->dispatch('unitDeleted');
        } catch (\Exception $e) {
            $this->dispatch('swal-error', 'No se pudo eliminar la unidad. Intenta de nuevo.');
        }
    }
}; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    {{-- Formulario de Registro --}}
    <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl p-8 shadow-sm">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">Nueva Unidad</h2>

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
            <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Nombre de la Unidad</label>
            @error('name') <p class="text-red-500 text-xs font-semibold mb-1">{{ $message }}</p> @enderror
            <flux:input wire:model="name" placeholder="ej. Jade, Coral, Amber" />
        </div>

        {{-- Precios --}}
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div>
                <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Precio por Día</label>
                @error('price_per_day') <p class="text-red-500 text-xs font-semibold mb-1">{{ $message }}</p> @enderror
                <flux:input wire:model="price_per_day" type="number" step="0.01" min="0" placeholder="0.00" />
            </div>
            <div>
                <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Precio por Hora</label>
                @error('price_per_hour') <p class="text-red-500 text-xs font-semibold mb-1">{{ $message }}</p> @enderror
                <flux:input wire:model="price_per_hour" type="number" step="0.01" min="0" placeholder="0.00" />
            </div>
        </div>

        {{-- Imagen --}}
        <div class="mb-5">
            <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Imagen de la Unidad</label>
            <input type="file" wire:model="image" accept="image/*"
                class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-[#4a5d41] file:text-white hover:file:bg-[#3d4d35] file:cursor-pointer" />
            @error('image') <p class="text-red-500 text-xs font-semibold mt-1">{{ $message }}</p> @enderror
            @if($image)
                <div class="mt-3">
                    <img src="{{ $image->temporaryUrl() }}" class="h-20 rounded-lg object-cover" />
                </div>
            @endif
        </div>

        {{-- Notas --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Especificaciones</label>
            @error('notes') <p class="text-red-500 text-xs font-semibold mb-1">{{ $message }}</p> @enderror
            <flux:textarea wire:model="notes" placeholder="ej. 2 camas, AC, Cocina, Vista al lago..." rows="3" />
        </div>

        <div class="flex gap-3">
            <button wire:click="save" type="button"
                class="flex-1 py-3 bg-[#4a5d41] hover:bg-[#3d4d35] text-white font-bold rounded-xl transition-colors">
                Agregar Unidad
            </button>
        </div>
    </div>

    {{-- Panel derecho --}}
    {{-- Unidades Registradas --}}
    <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl p-8 shadow-sm flex flex-col">
        <h3 class="text-base font-bold text-zinc-900 dark:text-white mb-4">Unidades Registradas</h3>
        <div class="flex-1 space-y-2">
            @forelse($unitsList as $u)
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
                        @php $occupied = $u['is_occupied'] ?? false; @endphp

                        @if($occupied)
                            <span class="p-1.5 text-zinc-300 dark:text-zinc-600 cursor-not-allowed rounded-lg" title="Unidad ocupada - no se puede editar">
                                <flux:icon name="lock-closed" class="size-4" />
                            </span>
                        @else
                            <button wire:click="editUnit({{ $u['id'] }})" class="p-1.5 text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-950/30 transition-colors" title="Editar">
                                <flux:icon name="pencil" class="size-4" />
                            </button>
                        @endif

                        @if($occupied)
                            <span class="p-1.5 text-zinc-300 dark:text-zinc-600 cursor-not-allowed rounded-lg" title="Unidad ocupada - no se puede eliminar">
                                <flux:icon name="lock-closed" class="size-4" />
                            </span>
                        @else
                            <button wire:click="confirmDelete({{ $u['id'] }})"
                                class="p-1.5 text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors" title="Eliminar">
                                <flux:icon name="trash" class="size-4" />
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-6">
                    <flux:icon name="home" class="size-10 text-zinc-200 mx-auto mb-2" />
                    <p class="text-sm text-zinc-400">No hay unidades registradas aún</p>
                </div>
            @endforelse
        </div>

        {{-- Paginación --}}
        @if($unitsList->hasPages())
            <div class="mt-auto pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400 mb-2">
                    <span>Mostrando {{ $unitsList->firstItem() }} a {{ $unitsList->lastItem() }} de {{ $unitsList->total() }} unidades</span>
                </div>
                <div class="flex items-center justify-center gap-1">
                    @if($unitsList->onFirstPage())
                        <span class="px-3 py-1.5 rounded-lg text-zinc-300 dark:text-zinc-600 cursor-not-allowed">&lsaquo;</span>
                    @else
                        <button wire:click="previousPage" class="px-3 py-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">&lsaquo;</span>
                    @endif

                    @foreach($unitsList->getUrlRange(max(1, $unitsList->currentPage() - 2), min($unitsList->lastPage(), $unitsList->currentPage() + 2)) as $page => $url)
                        @if($page == $unitsList->currentPage())
                            <span class="px-3 py-1.5 rounded-lg bg-[#4a5d41] text-white font-bold text-xs">{{ $page }}</span>
                        @else
                            <button wire:click="gotoPage({{ $page }})" class="px-3 py-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors text-xs">{{ $page }}</button>
                        @endif
                    @endforeach

                    @if($unitsList->hasMorePages())
                        <button wire:click="nextPage" class="px-3 py-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">&rsaquo;</span>
                    @else
                        <span class="px-3 py-1.5 rounded-lg text-zinc-300 dark:text-zinc-600 cursor-not-allowed">&rsaquo;</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Modal para Editar Unidad --}}
    <flux:modal name="edit-unit-modal" class="md:w-full md:max-w-xl">
        <div class="p-6">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">Editar Unidad</h2>

            {{-- Tipo --}}
            <div class="mb-6">
                <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-3">Tipo de Unidad</p>
                <div class="grid grid-cols-3 gap-2 sm:gap-3">
                    @foreach(['bungalow' => ['label' => 'Bungalow', 'icon' => 'home'], 'rv' => ['label' => 'RV Spot', 'icon' => 'bolt'], 'camping' => ['label' => 'Camping', 'icon' => 'map-pin']] as $key => $item)
                        <button type="button" wire:click="$set('edit_type', '{{ $key }}')"
                            class="flex flex-col items-center justify-center gap-1.5 sm:gap-2 p-2 sm:p-4 rounded-xl sm:rounded-2xl border-2 transition
                                {{ $edit_type === $key ? 'border-[#4a5d41] bg-[#4a5d41]/5 text-[#4a5d41]' : 'border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:border-zinc-300' }}">
                            <flux:icon name="{{ $item['icon'] }}" class="size-6 sm:size-7 shrink-0" />
                            <span class="text-[11px] sm:text-sm font-bold truncate w-full text-center leading-tight">{{ $item['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Nombre --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Nombre de la Unidad</label>
                @error('edit_name') <p class="text-red-500 text-xs font-semibold mb-1">{{ $message }}</p> @enderror
                <flux:input wire:model="edit_name" placeholder="ej. Jade, Coral, Amber" />
            </div>

            {{-- Precios --}}
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Precio por Día</label>
                    @error('edit_price_per_day') <p class="text-red-500 text-xs font-semibold mb-1">{{ $message }}</p> @enderror
                    <flux:input wire:model="edit_price_per_day" type="number" step="0.01" min="0" placeholder="0.00" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Precio por Hora</label>
                    @error('edit_price_per_hour') <p class="text-red-500 text-xs font-semibold mb-1">{{ $message }}</p> @enderror
                    <flux:input wire:model="edit_price_per_hour" type="number" step="0.01" min="0" placeholder="0.00" />
                </div>
            </div>

            {{-- Imagen --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Imagen de la Unidad</label>
                <input type="file" wire:model="edit_image" accept="image/*"
                    class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-[#4a5d41] file:text-white hover:file:bg-[#3d4d35] file:cursor-pointer" />
                @error('edit_image') <p class="text-red-500 text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                @if($edit_image)
                    <div class="mt-3">
                        <img src="{{ $edit_image->temporaryUrl() }}" class="h-20 rounded-lg object-cover" />
                    </div>
                @elseif($editingUnit && $editingUnit->image)
                    <div class="mt-3">
                        <p class="text-xs text-zinc-500 mb-1">Imagen actual:</p>
                        <img src="{{ asset('storage/' . $editingUnit->image) }}" class="h-20 rounded-lg object-cover" />
                    </div>
                @endif
            </div>

            {{-- Notas --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Especificaciones</label>
                @error('edit_notes') <p class="text-red-500 text-xs font-semibold mb-1">{{ $message }}</p> @enderror
                <flux:textarea wire:model="edit_notes" placeholder="ej. 2 camas, AC, Cocina, Vista al lago..." rows="3" />
            </div>

            <div class="flex gap-3">
                <button wire:click="updateUnit" type="button"
                    class="flex-1 py-3 bg-[#4a5d41] hover:bg-[#3d4d35] text-white font-bold rounded-xl transition-colors">
                    Actualizar Unidad
                </button>
                <button type="button" x-on:click="$flux.modal('edit-unit-modal').close()"
                    class="px-6 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-bold rounded-xl hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors">
                    Cancelar
                </button>
            </div>
        </div>
    </flux:modal>

    @script
    <script>
        $wire.on('unitCreated', () => {
            Swal.fire({
                title: '¡Unidad Registrada!',
                text: 'La unidad ha sido creada exitosamente.',
                icon: 'success',
                confirmButtonColor: '#4a5d41',
                confirmButtonText: 'Aceptar',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
            });
        });

        $wire.on('unitUpdated', () => {
            Swal.fire({
                title: '¡Unidad Actualizada!',
                text: 'Los cambios han sido guardados exitosamente.',
                icon: 'success',
                confirmButtonColor: '#4a5d41',
                confirmButtonText: 'Aceptar',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
            });
        });

        $wire.on('swal-confirm-delete', (unitName) => {
            Swal.fire({
                title: '¿Eliminar unidad?',
                text: '¿Estás seguro de que deseas eliminar "' + unitName + '"? Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4a5d41',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $wire.deleteUnit();
                }
            });
        });

        $wire.on('unitDeleted', () => {
            Swal.fire({
                title: '¡Unidad Eliminada!',
                text: 'La unidad ha sido eliminada exitosamente.',
                icon: 'success',
                confirmButtonColor: '#4a5d41',
                confirmButtonText: 'Aceptar',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
            });
        });

        $wire.on('swal-unit-occupied', (data) => {
            const d = Array.isArray(data) ? data[0] : data;
            const actionLabels = {
                'editar': 'editada',
                'eliminar': 'eliminada',
            };
            Swal.fire({
                title: 'Unidad ocupada',
                html: `<div style="text-align: center;">
                    <div style="font-size: 1.1rem; margin-bottom: 0.25rem;">
                        La unidad <strong>${d.unit}</strong> no puede ser ${actionLabels[d.action] || 'modificada'}
                    </div>
                    <div style="font-size: 0.95rem; color: #71717a;">
                        porque se encuentra <strong style="color: #ef4444;">ocupada</strong> actualmente.
                    </div>
                </div>`,
                icon: 'warning',
                confirmButtonColor: '#4a5d41',
                confirmButtonText: 'Entendido',
                customClass: {
                    confirmButton: 'swal2-confirm-green',
                },
                buttonsStyling: false,
            });
        });
    </script>
    @endscript
</div>