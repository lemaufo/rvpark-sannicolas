<header class="h-24 bg-white dark:bg-zinc-900 border-b border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between px-10 sticky top-0 z-40 backdrop-blur-md bg-white/80 dark:bg-zinc-900/80">
    <!-- Mobile Toggle & Brand -->
    <div class="flex items-center gap-5 lg:hidden">
        <button id="mobile-sidebar-toggle" class="p-2.5 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 rounded-2xl transition-colors">
            <flux:icon name="bars-3" class="size-7" />
        </button>
        <div class="flex flex-col leading-tight">
            <span class="font-black text-zinc-900 dark:text-white tracking-tight">San Nicolás</span>
            <span class="text-[10px] text-zinc-400 font-bold uppercase tracking-widest">Admin</span>
        </div>
    </div>

    <!-- Desktop Welcome -->
    <div class="hidden lg:block">
        <span class="text-[10px] text-zinc-400 font-bold uppercase tracking-widest">Panel de Administración</span>
    </div>

    <!-- Right Side Actions -->
    <div class="flex items-center gap-6">
        <!-- Search Button (Icon only) -->
        <button class="p-3 text-zinc-400 dark:text-zinc-500 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-50 dark:hover:bg-zinc-800/50 rounded-2xl transition-all duration-200">
            <flux:icon name="magnifying-glass" class="size-5" />
        </button>

        <!-- Mobile Profile Dropdown (Only visible on small screens) -->
        <div class="lg:hidden">
            <flux:dropdown position="bottom" align="end">
                <flux:profile :initials="auth()->user()->initials()" class="cursor-pointer" />
                <flux:menu class="w-64 p-2 rounded-2xl shadow-2xl">
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
        
        <!-- Notification Button -->
        <button class="relative p-3 text-zinc-400 dark:text-zinc-500 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-50 dark:hover:bg-zinc-800/50 rounded-2xl transition-all duration-200">
            <flux:icon name="bell" class="size-5" />
            <span class="absolute top-2.5 right-2.5 size-2 bg-red-500 rounded-full border-2 border-white dark:border-zinc-900"></span>
        </button>
    </div>
</header>
