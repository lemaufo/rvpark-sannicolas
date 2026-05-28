<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Unit;

class AvailabilityService
{
    /**
     * Verifica si una unidad está disponible en las fechas dadas.
     * 
     * @param int $unitId
     * @param string $checkIn (Y-m-d)
     * @param string $checkOut (Y-m-d)
     * @return bool
     */
    public function isAvailable(int $unitId, string $checkIn, string $checkOut): bool
    {
        // Revisamos si existe alguna reserva activa que se solape (overlap) con estas fechas
        $overlapping = Reservation::where('unit_id', $unitId)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut])
                      ->orWhereBetween('check_out', [$checkIn, $checkOut])
                      ->orWhere(function ($q) use ($checkIn, $checkOut) {
                          $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                      });
            })
            ->exists();

        return !$overlapping;
    }

    /**
     * Retorna todas las unidades disponibles para un rango de fechas.
     * Opcionalmente filtra por tipo.
     */
    public function getAvailableUnits(string $checkIn, string $checkOut, ?string $type = null)
    {
        // Obtenemos los IDs de las unidades ocupadas en esas fechas
        $occupiedUnitIds = Reservation::whereIn('status', ['confirmed', 'checked_in'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut])
                      ->orWhereBetween('check_out', [$checkIn, $checkOut])
                      ->orWhere(function ($q) use ($checkIn, $checkOut) {
                          $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                      });
            })
            ->pluck('unit_id');

        $query = Unit::whereNotIn('id', $occupiedUnitIds);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->get();
    }
}
