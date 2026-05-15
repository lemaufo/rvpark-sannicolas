@props(['active' => 'dashboard'])

<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-zinc-200 transition-transform duration-300 transform -translate-x-full lg:translate-x-0 lg:static lg:inset-0">
    <div class="flex flex-col h-full">
        <!-- Branding -->
        <div class="p-8 flex justify-center border-b border-zinc-50">
            <a href="{{ route('dashboard') }}" class="group">
                <img src="{{ asset('logo_triangular.png') }}" alt="Logo" style="width: 145px; height: auto;"
                    class="object-contain transition-transform duration-300 group-hover:scale-105">
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            @php
                $navItems = [
                    ['id' => 'dashboard', 'label' => 'Panel', 'icon' => 'home', 'route' => 'dashboard'],
                    ['id' => 'inventario', 'label' => 'Inventario', 'icon' => 'archive-box', 'route' => 'inventario'],
                    ['id' => 'reservas', 'label' => 'Reservaciones', 'icon' => 'calendar', 'route' => 'reservas'],
                    ['id' => 'registro', 'label' => 'Registro', 'icon' => 'document-text', 'route' => 'registro'],
                    ['id' => 'configuracion', 'label' => 'Configuración', 'icon' => 'cog-6-tooth', 'route' => 'configuracion'],
                ];
            @endphp

            @foreach($navItems as $item)
                <a href="{{ route($item['route']) }}"
                    class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-300 {{ $active === $item['id'] ? 'bg-[#4a5d41] text-white shadow-lg shadow-brand-green/20' : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-900' }}">
                    <flux:icon :name="$item['icon']" variant="outline"
                        class="size-5 {{ $active === $item['id'] ? 'text-white' : 'text-zinc-400 group-hover:text-zinc-600' }}" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <!-- Profile Section (Bottom) -->
        <div class="p-4 mt-auto border-t border-zinc-50">
            <flux:dropdown position="top" align="start">
                <button class="w-full flex items-center gap-3 p-3 hover:bg-zinc-50 rounded-2xl transition-colors group">
                    <div class="size-10 rounded-xl bg-zinc-900 flex items-center justify-center text-white text-xs font-black shadow-lg shadow-zinc-200">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div class="flex-1 text-left">
                        <p class="text-sm font-bold text-zinc-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-zinc-400 font-bold uppercase tracking-widest truncate">Administrador</p>
                    </div>
                    <flux:icon name="chevrons-up-down" class="size-4 text-zinc-400 group-hover:text-zinc-600 transition-colors" />
                </button>

                <flux:menu class="w-64 p-2 rounded-2xl shadow-2xl">
                    <div class="px-3 py-2 mb-2">
                        <p class="text-xs font-black text-zinc-400 uppercase tracking-widest">Cuenta</p>
                        <p class="text-sm font-bold text-zinc-900 mt-1">{{ auth()->user()->email }}</p>
                    </div>
                    <flux:menu.separator class="mb-2" />
                    <flux:menu.item icon="user" :href="route('settings.profile')" wire:navigate class="rounded-xl font-semibold">Mi Perfil</flux:menu.item>
                    <flux:menu.item icon="cog" :href="route('configuracion')" wire:navigate class="rounded-xl font-semibold">Configuración</flux:menu.item>
                    <flux:menu.separator class="my-2" />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right" class="w-full text-red-600 rounded-xl font-bold">
                            Cerrar Sesión
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>

    </div>
</aside>