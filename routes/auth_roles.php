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

        // Dashboard principal del administrador
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Gestión de usuarios (ejemplo)
        Route::get('/usuarios', function () {
            return view('admin.usuarios.index');
        })->name('usuarios.index');
        

        // Aquí irán las rutas de Eduardo cuando estén listas
        // Route::resource('/lotes', LoteController::class);
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
            return view('receptionist.dashboard');
        })->name('index'); // → nombre final: dashboard.index
    });


// ─────────────────────────────────────────────────────────────────────────────
// GRUPO RECEPCIONISTA — Reservas
// Acceso restringido a usuarios autenticados con rol 'receptionist'
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'role:receptionist'])
    ->prefix('reservas')
    ->name('reservas.')
    ->group(function () {

        // Vista principal de reservas
        Route::get('/', function () {
            return view('receptionist.reservas.index');
        })->name('index');

        // Detalle de una reserva
        Route::get('/{id}', function ($id) {
            return view('receptionist.reservas.show', compact('id'));
        })->name('show');

        // Aquí irán las rutas de Eduardo cuando estén listas
        // Route::resource('/', ReservaController::class);
    });
