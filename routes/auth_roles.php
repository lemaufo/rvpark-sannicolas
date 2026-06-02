<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Auth + Roles — RV Park San Nicolás
|--------------------------------------------------------------------------
|
| Este archivo es cargado desde bootstrap/app.php usando el parámetro `then:`
| de withRouting(), por lo tanto NO es necesario tocarlo en web.php.
|
| Convención de roles:
|   'admin'         → Administrador del sistema
|   'receptionist'  → Personal de recepción / atención al cliente
|
*/

// ─────────────────────────────────────────────────────────────────────────────
// GRUPO ADMIN
// Acceso restringido a usuarios autenticados con rol 'admin'
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard principal del administrador — con datos reales de BD
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'adminIndex'])
            ->name('dashboard');

        // Gestión de usuarios (ejemplo)
        Route::get('/usuarios', function () {
            return view('admin.usuarios.index');
        })->name('usuarios.index');


        // ── Reportes ───────────────────────────────────────────────────────
        Route::prefix('reportes')->name('reportes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ReportController::class, 'index'])->name('index');
            Route::get('/ocupacion', [\App\Http\Controllers\ReportController::class, 'ocupacionPorDia'])->name('ocupacion');
            Route::get('/historial', [\App\Http\Controllers\ReportController::class, 'historialReservaciones'])->name('historial');
            Route::get('/exportar-csv', [\App\Http\Controllers\ReportController::class, 'exportarCSV'])->name('exportar');
        });
    });


// ─────────────────────────────────────────────────────────────────────────────
// GRUPO RECEPCIONISTA — Dashboard principal
// Punto de entrada tras el login. Ruta: GET /recepcionista/dashboard
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'role:receptionist'])
    ->prefix('recepcionista')
    ->name('dashboard.')
    ->group(function () {

        // Dashboard principal del recepcionista (destino post-login)
        Route::get('/dashboard', function () {
            return view('dashboard'); // usa dashboard.blade.php
        })->name('index'); // → nombre final: dashboard.index
    });


// ─────────────────────────────────────────────────────────────────────────────
// NOTA: Las rutas del panel (inventario, reservas, registro, configuracion)
// están definidas en panel.php para evitar conflictos de nombres de ruta.
// Este archivo solo contiene rutas con control de roles.
// ─────────────────────────────────────────────────────────────────────────────
