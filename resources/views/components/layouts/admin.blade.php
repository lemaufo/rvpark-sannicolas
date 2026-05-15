<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
    <head>
        @include('partials.head')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <title>{{ $title ?? 'Admin - San Nicolás' }}</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap');
            body {
                font-family: 'Instrument Sans', sans-serif;
                letter-spacing: -0.01em;
            }
        </style>
    </head>
    <body class="min-h-screen bg-[#f9fafb] flex">
        <!-- Sidebar -->
        <x-layouts.admin.sidebar :active="$active ?? 'dashboard'" />

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <x-layouts.admin.header />

            <!-- Main Area -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="max-w-7xl mx-auto">
                    {{ $slot }}
                </div>
            </main>
        </div>

        @fluxScripts
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const toggle = document.getElementById('mobile-sidebar-toggle');
                const sidebar = document.getElementById('sidebar');
                
                if (toggle && sidebar) {
                    toggle.addEventListener('click', () => {
                        sidebar.classList.toggle('-translate-x-full');
                    });

                    // Close sidebar when clicking outside on mobile
                    document.addEventListener('click', (e) => {
                        if (!sidebar.contains(e.target) && !toggle.contains(e.target) && !sidebar.classList.contains('-translate-x-full')) {
                            sidebar.classList.add('-translate-x-full');
                        }
                    });
                }
            });
        </script>
    </body>
</html>
