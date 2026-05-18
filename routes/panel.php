<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified'])->group(function () {
    // Un solo /dashboard para todos los roles
    Route::get('dashboard', function () {
        $view = match (auth()->user()->role) {
            'admin' => 'panel',
            'receptionist' => 'receptionist.dashboard',
            default => 'dashboard',
        };
        return view($view);
    })->name('dashboard');

    Route::view('inventario', 'inventario')->name('inventario');
    Route::view('reservas', 'reservas')->name('reservas');
    Route::view('registro', 'registro')->name('registro');
    Route::view('configuracion', 'configuracion')->name('configuracion');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});
