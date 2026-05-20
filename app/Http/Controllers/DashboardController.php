<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Unit;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Panel principal del Administrador.
     *
     * Ejecuta consultas Eloquent optimizadas y pasa las variables
     * reales a resources/views/admin/dashboard.blade.php.
     */
    public function adminIndex()
    {
        // ── Métricas de ocupación ─────────────────────────────────────────────
        $totalUnidades      = Unit::count();
        $unidadesOcupadas   = Unit::where('status', 'occupied')->count();
        $porcentajeOcupacion = $totalUnidades > 0
            ? round(($unidadesOcupadas / $totalUnidades) * 100)
            : 0;

        // ── Disponibilidad por tipo ───────────────────────────────────────────
        // Resultado: colección tipo ['rv' => 4, 'cabin' => 1, ...]
        $disponiblesPorTipo = Unit::where('status', 'available')
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $totalDisponibles = $disponiblesPorTipo->sum();

        // ── Movimientos del día ───────────────────────────────────────────────
        $checkinsHoy = Reservation::whereDate('check_in', Carbon::today())
            ->with('unit')
            ->get();

        $checkoutsHoy = Reservation::whereDate('check_out', Carbon::today())
            ->with('unit')
            ->get();

        // ── Estadísticas de canales ───────────────────────────────────────────
        // Placeholders en 0 — el campo 'source' aún no existe en la BD.
        $statsDirectas = 0;
        $statsTelefono = 0;
        $statsWeb      = 0;
        $statsTotal    = $checkinsHoy->count() + $checkoutsHoy->count();

        return view('admin.dashboard', compact(
            'totalUnidades',
            'unidadesOcupadas',
            'porcentajeOcupacion',
            'disponiblesPorTipo',
            'totalDisponibles',
            'checkinsHoy',
            'checkoutsHoy',
            'statsDirectas',
            'statsTelefono',
            'statsWeb',
            'statsTotal',
        ));
    }
}
