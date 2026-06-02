<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Unit;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function ocupacionPorDia(Request $request)
    {
        $totalUnits = Unit::count();

        $startDate = $request->get('desde', Carbon::now()->subMonth()->toDateString());
        $endDate   = $request->get('hasta', Carbon::now()->toDateString());

        $reservations = Reservation::whereIn('status', ['confirmed', 'checked_in'])
            ->get(['check_in', 'check_out']);

        $data = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $occupied = $reservations->filter(function ($r) use ($dateStr) {
                return $r->check_in <= $dateStr && $r->check_out >= $dateStr;
            })->count();

            $data[] = [
                'fecha'      => $dateStr,
                'ocupadas'   => $occupied,
                'total'      => $totalUnits,
                'porcentaje' => $totalUnits > 0 ? round(($occupied / $totalUnits) * 100, 1) : 0,
            ];
        }

        return view('reports.ocupacion', compact('data', 'startDate', 'endDate', 'totalUnits'));
    }

    public function historialReservaciones(Request $request)
    {
        $query = Reservation::with('unit');

        if ($request->filled('desde')) {
            $query->where('check_in', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->where('check_out', '<=', $request->hasta);
        }
        if ($request->filled('tipo')) {
            $query->whereHas('unit', fn($q) => $q->where('type', $request->tipo));
        }

        $reservations = $query->orderBy('check_in', 'desc')->get();

        $tipos = ['bungalow', 'rv', 'camping'];

        return view('reports.historial', compact('reservations', 'tipos'));
    }

    public function exportarCSV(Request $request)
    {
        $query = Reservation::with('unit');

        if ($request->filled('desde')) {
            $query->where('check_in', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->where('check_out', '<=', $request->hasta);
        }
        if ($request->filled('tipo')) {
            $query->whereHas('unit', fn($q) => $q->where('type', $request->tipo));
        }

        $reservations = $query->orderBy('check_in', 'desc')->get();

        $statusMap = [
            'pending'     => 'Pendiente',
            'confirmed'   => 'Confirmada',
            'checked_in'  => 'Activa',
            'checked_out' => 'Completada',
            'cancelled'   => 'Cancelada',
        ];

        $filename = 'Reservaciones-' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($reservations, $statusMap) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['No.', 'Huésped', 'Teléfono', 'Unidad', 'Tipo', 'Entrada', 'Salida', 'Noches', 'Estado', 'Total', 'Motivo']);

            foreach ($reservations as $i => $r) {
                $checkIn = Carbon::parse($r->check_in);
                $checkOut = Carbon::parse($r->check_out);

                fputcsv($output, [
                    $i + 1,
                    $r->guest_name,
                    $r->guest_phone ? "'" . $r->guest_phone : '',
                    $r->unit?->name ?? 'N/A',
                    match ($r->unit?->type) {
                        'bungalow' => 'Bungalow',
                        'rv'       => 'RV Spot',
                        'camping'  => 'Camping',
                        default    => $r->unit?->type ?? 'N/A',
                    },
                    $checkIn->format('d/m/Y'),
                    $checkOut->format('d/m/Y'),
                    $checkIn->diffInDays($checkOut),
                    $statusMap[$r->status] ?? $r->status,
                    '$' . number_format($r->total_amount, 2),
                    $r->cancel_reason ?? '',
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
