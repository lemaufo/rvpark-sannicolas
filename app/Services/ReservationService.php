<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Unit;
use App\Models\OperationalStatus;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    protected AvailabilityService $availabilityService;

    // Tarifas base
    const RATES = [
        'bungalow' => 1500,
        'rv' => 800,
        'camping' => 350
    ];

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Crea una nueva reserva.
     */
    public function createReservation(array $data): Reservation
    {
        $unit = Unit::findOrFail($data['unit_id']);
        
        // 1. Verificar disponibilidad
        if (!$this->availabilityService->isAvailable($unit->id, $data['check_in'], $data['check_out'])) {
            throw new Exception("La unidad no está disponible en las fechas seleccionadas.");
        }

        // 2. Calcular tarifa
        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $nights = $checkIn->diffInDays($checkOut);
        if ($nights == 0) {
            $nights = 1; // Mínimo 1 noche
        }

        $rate = self::RATES[$unit->type] ?? 0;
        $totalAmount = $rate * $nights;

        // 3. Crear reserva
        return Reservation::create([
            'unit_id' => $unit->id,
            'guest_name' => $data['guest_name'],
            'guest_phone' => $data['guest_phone'] ?? null,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'status' => 'pending', // Nace como pending hasta que se confirme el pago o registro
            'total_amount' => $totalAmount
        ]);
    }

    /**
     * Confirma una reserva y ocupa la unidad si el check-in es hoy.
     */
    public function confirmReservation(Reservation $reservation, $userId)
    {
        return DB::transaction(function () use ($reservation, $userId) {
            $reservation->update(['status' => 'confirmed']);

            $checkIn = Carbon::parse($reservation->check_in);
            
            // Si la fecha de llegada es hoy, la marcamos físicamente como ocupada de una vez
            if ($checkIn->isToday() || $checkIn->isPast()) {
                $unit = $reservation->unit;
                $unit->update(['status' => 'occupied']);
                
                OperationalStatus::create([
                    'unit_id' => $unit->id,
                    'status' => 'occupied',
                    'user_id' => $userId,
                    'changed_at' => now()
                ]);
            }

            return $reservation;
        });
    }

    /**
     * Cancela una reserva y libera la unidad.
     */
    public function cancelReservation(Reservation $reservation, $userId)
    {
        return DB::transaction(function () use ($reservation, $userId) {
            $reservation->update(['status' => 'cancelled']);

            $unit = $reservation->unit;
            // Si la unidad estaba ocupada por causa de esta reserva, la liberamos
            if ($unit->status === 'occupied') {
                $unit->update(['status' => 'available']);
                
                OperationalStatus::create([
                    'unit_id' => $unit->id,
                    'status' => 'available',
                    'user_id' => $userId,
                    'changed_at' => now()
                ]);
            }

            return $reservation;
        });
    }
}
