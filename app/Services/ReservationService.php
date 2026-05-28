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

    const RATES = [
        'bungalow' => 1500,
        'rv' => 800,
        'camping' => 350
    ];

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function createReservation(array $data): Reservation
    {
        $unit = Unit::findOrFail($data['unit_id']);

        if (!$this->availabilityService->isAvailable($unit->id, $data['check_in'], $data['check_out'])) {
            throw new Exception("La unidad no está disponible en las fechas seleccionadas.");
        }

        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $nights = max(1, $checkIn->diffInDays($checkOut));
        $rate = self::RATES[$unit->type] ?? 0;

        return Reservation::create([
            'unit_id' => $unit->id,
            'guest_name' => $data['guest_name'],
            'guest_phone' => $data['guest_phone'] ?? null,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'status' => 'pending',
            'total_amount' => $rate * $nights
        ]);
    }

    public function confirmReservation(Reservation $reservation, $userId)
    {
        return DB::transaction(function () use ($reservation, $userId) {
            $reservation->update(['status' => 'confirmed']);

            $checkIn = Carbon::parse($reservation->check_in);

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

    public function cancelReservation(Reservation $reservation, $userId)
    {
        return DB::transaction(function () use ($reservation, $userId) {
            $reservation->update(['status' => 'cancelled']);

            $unit = $reservation->unit;
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

    public function getReservationsForCalendar(): array
    {
        $events = [];
        $reservations = Reservation::with('unit')->get();

        foreach ($reservations as $res) {
            $events[] = [
                'id' => 'real_' . $res->id,
                'title' => $res->guest_name,
                'start' => $res->check_in,
                'end' => $res->check_out,
                'allDay' => true,
                'extendedProps' => [
                    'is_mock' => false,
                    'guest_name' => $res->guest_name,
                    'guest_phone' => $res->guest_phone,
                    'unit_name' => $res->unit ? $res->unit->name : 'Unidad General',
                    'unit_type' => $res->unit ? strtolower($res->unit->type) : 'bungalow',
                    'total_amount' => (float) $res->total_amount,
                    'status' => $res->status,
                    'check_in' => $res->check_in,
                    'check_out' => $res->check_out,
                ]
            ];
        }

        return $events;
    }
}