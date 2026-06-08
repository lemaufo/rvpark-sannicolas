<x-layouts.app>
    <div class="p-6 lg:p-10 max-w-7xl mx-auto">
        <div class="mb-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight">Historial de Reservaciones</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mt-1 font-medium italic">{{ $reservations->count() }} registros encontrados</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.reportes.index') }}" wire:navigate
                   class="text-sm font-bold text-[#4a5d41] hover:underline underline-offset-4 flex items-center gap-1">
                    <flux:icon name="arrow-left" class="size-4" />
                    Volver
                </a>
                <a href="{{ route('admin.reportes.exportar', request()->query()) }}"
                   class="px-5 py-2.5 bg-[#4a5d41] text-white rounded-xl font-bold hover:bg-[#3d4d35] transition-colors shadow-sm flex items-center gap-2 text-sm">
                    <flux:icon name="arrow-down-tray" class="size-4" />
                    Exportar CSV
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-[2.5rem] p-8 shadow-sm mb-8">
            <form method="GET" action="{{ route('admin.reportes.historial') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Desde</label>
                    <input type="date" name="desde" value="{{ request('desde') }}"
                           class="rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-[#4a5d41]/20 focus:border-[#4a5d41] px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Hasta</label>
                    <input type="date" name="hasta" value="{{ request('hasta') }}"
                           class="rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-[#4a5d41]/20 focus:border-[#4a5d41] px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">Tipo de Unidad</label>
                    <select name="tipo"
                            class="rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm focus:ring-2 focus:ring-[#4a5d41]/20 focus:border-[#4a5d41] px-4 py-2">
                        <option value="">Todos</option>
                        @foreach($tipos as $t)
                            <option value="{{ $t }}" {{ request('tipo') === $t ? 'selected' : '' }}>
                                {{ ucfirst($t) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                        class="px-6 py-2.5 bg-[#4a5d41] text-white rounded-xl font-bold hover:bg-[#3d4d35] transition-colors shadow-sm">
                    Filtrar
                </button>
                @if(request()->anyFilled(['desde', 'hasta', 'tipo']))
                    <a href="{{ route('admin.reportes.historial') }}" wire:navigate
                       class="px-6 py-2.5 bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 rounded-xl font-bold hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors text-sm">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 rounded-[2.5rem] p-8 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="text-left py-3 pr-4 font-bold text-zinc-500 dark:text-zinc-400">Huésped</th>
                        <th class="text-left py-3 pr-4 font-bold text-zinc-500 dark:text-zinc-400">Unidad</th>
                        <th class="text-left py-3 pr-4 font-bold text-zinc-500 dark:text-zinc-400">Tipo</th>
                        <th class="text-left py-3 pr-4 font-bold text-zinc-500 dark:text-zinc-400">Check-in</th>
                        <th class="text-left py-3 pr-4 font-bold text-zinc-500 dark:text-zinc-400">Check-out</th>
                        <th class="text-left py-3 pr-4 font-bold text-zinc-500 dark:text-zinc-400">Estado</th>
                        <th class="text-right py-3 pl-4 font-bold text-zinc-500 dark:text-zinc-400">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reservations as $r)
                        <tr class="border-b border-zinc-50 dark:border-zinc-800/50 hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="py-3 pr-4">
                                <div class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $r->guest_name }}</div>
                                <div class="text-xs text-zinc-400">{{ $r->guest_phone }}</div>
                            </td>
                            <td class="py-3 pr-4 text-zinc-600 dark:text-zinc-300">{{ $r->unit?->name ?? 'N/A' }}</td>
                            <td class="py-3 pr-4">
                                <span class="inline-block px-2.5 py-0.5 rounded-lg text-xs font-bold capitalize
                                    {{ $r->unit?->type === 'bungalow' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                                    {{ $r->unit?->type === 'rv' ? 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400' : '' }}
                                    {{ $r->unit?->type === 'camping' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' : '' }}">
                                    {{ $r->unit?->type ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-zinc-600 dark:text-zinc-300">{{ \Carbon\Carbon::parse($r->check_in)->format('d/m/Y') }}</td>
                            <td class="py-3 pr-4 text-zinc-600 dark:text-zinc-300">{{ \Carbon\Carbon::parse($r->check_out)->format('d/m/Y') }}</td>
                            <td class="py-3 pr-4">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold
                                    {{ $r->status === 'confirmed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                                    {{ $r->status === 'checked_in' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                    {{ $r->status === 'checked_out' ? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' : '' }}
                                    {{ $r->status === 'cancelled' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                    {{ $r->status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : '' }}">
                                    {{ match($r->status) {
                                        'confirmed' => 'Confirmada',
                                        'checked_in' => 'Activa',
                                        'checked_out' => 'Completada',
                                        'cancelled' => 'Cancelada',
                                        'pending' => 'Pendiente',
                                        default => $r->status,
                                    } }}
                                </span>
                                @if($r->status === 'cancelled' && $r->cancel_reason)
                                    <div class="text-xs text-red-400 mt-0.5">{{ $r->cancel_reason }}</div>
                                @endif
                            </td>
                            <td class="py-3 pl-4 text-right font-bold text-[#4a5d41] dark:text-emerald-400">
                                ${{ number_format($r->total_amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <flux:icon name="document-text" class="size-12 text-zinc-200 dark:text-zinc-700 mx-auto mb-4" />
                                <p class="text-zinc-500 dark:text-zinc-400 font-medium">No se encontraron reservaciones con los filtros aplicados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
