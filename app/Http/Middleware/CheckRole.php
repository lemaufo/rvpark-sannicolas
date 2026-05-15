<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Verifica que el usuario autenticado tenga el rol requerido.
     * El rol se almacena en el campo `role` de la tabla `users`
     * con los valores posibles: 'admin' | 'receptionist'.
     *
     * Uso en rutas: middleware('role:admin')  o  middleware('role:receptionist')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Si no hay sesión activa, redirigir al login
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // Comparar el rol del usuario con el rol requerido por la ruta
        if ($request->user()->role !== $role) {
            // Redirigir según el rol real del usuario (evitar bucle de 403)
            return match ($request->user()->role) {
                'admin'          => redirect()->route('admin.dashboard'),
                'receptionist'   => redirect()->route('reservas.index'),
                default          => abort(403, 'No tienes permiso para acceder a esta sección.'),
            };
        }

        return $next($request);
    }
}
