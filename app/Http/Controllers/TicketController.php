<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketController extends Controller
{
    public function reserva(Reservation $reservation)
    {
        $reservation->load('unit');

        $logoData = null;
        $logoPath = public_path('logo_triangular.png');
        if (file_exists($logoPath)) {
            $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $timestamp = now()->format('Ymd_His');
        $filename = "ticket-RV-{$reservation->id}-{$timestamp}.pdf";

        $pdf = Pdf::loadView('tickets.reserva', compact('reservation', 'logoData'))
            ->setPaper('letter')
            ->setOption('isRemoteEnabled', true);

        return $pdf->download($filename);
    }
}
