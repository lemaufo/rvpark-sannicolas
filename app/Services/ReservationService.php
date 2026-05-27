<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Unit;
use Carbon\Carbon;

class ReservationService
{
    /**
     * Get bookings in FullCalendar event format.
     */
    public function getReservationsForCalendar(): array
    {
        $events = [];

        // 1. Fetch real reservations from the database
        $reservations = Reservation::with('unit')->get();

        foreach ($reservations as $res) {
            $unitType = $res->unit ? strtolower($res->unit->type) : 'bungalow';
            $unitName = $res->unit ? $res->unit->name : 'Unidad General';
            
            $events[] = [
                'id' => 'real_' . $res->id,
                'title' => $res->guest_name,
                'start' => $res->check_in,
                'end' => $res->check_out, // FullCalendar end is exclusive for all-day events
                'allDay' => true,
                'extendedProps' => [
                    'is_mock' => false,
                    'guest_name' => $res->guest_name,
                    'guest_phone' => $res->guest_phone,
                    'unit_name' => $unitName,
                    'unit_type' => $unitType,
                    'total_amount' => (float)$res->total_amount,
                    'status' => $res->status,
                    'check_in' => $res->check_in,
                    'check_out' => $res->check_out,
                ]
            ];
        }

        // 2. Generate high-quality mock reservations around the current month (or any month)
        // to show off the coloring, view toggle, and grouping (+5 más).
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;
        
        $mockReservations = [
            // Multiple overlapping events on day 20 to trigger grouping (+5 más)
            [
                'guest_name' => 'Anderson, K.',
                'guest_phone' => '+52 55 1234 5678',
                'unit_name' => 'Bungalow Superior A1',
                'unit_type' => 'bungalow',
                'check_in' => sprintf('%04d-%02d-20', $year, $month),
                'check_out' => sprintf('%04d-%02d-23', $year, $month),
                'total_amount' => 3000.00,
                'status' => 'confirmed',
            ],
            [
                'guest_name' => 'Familia García',
                'guest_phone' => '+52 33 9876 5432',
                'unit_name' => 'RV 1',
                'unit_type' => 'rv',
                'check_in' => sprintf('%04d-%02d-20', $year, $month),
                'check_out' => sprintf('%04d-%02d-22', $year, $month),
                'total_amount' => 1000.00,
                'status' => 'checked_in',
            ],
            [
                'guest_name' => 'Familia Taylor',
                'guest_phone' => '+1 415 555 2671',
                'unit_name' => 'Camping 1',
                'unit_type' => 'camping',
                'check_in' => sprintf('%04d-%02d-20', $year, $month),
                'check_out' => sprintf('%04d-%02d-24', $year, $month),
                'total_amount' => 800.00,
                'status' => 'confirmed',
            ],
            [
                'guest_name' => 'Martínez, J.',
                'guest_phone' => '+52 81 2345 6789',
                'unit_name' => 'Bungalow Superior A2',
                'unit_type' => 'bungalow',
                'check_in' => sprintf('%04d-%02d-20', $year, $month),
                'check_out' => sprintf('%04d-%02d-25', $year, $month),
                'total_amount' => 5000.00,
                'status' => 'confirmed',
            ],
            [
                'guest_name' => 'Smith, John',
                'guest_phone' => '+1 212 555 0199',
                'unit_name' => 'RV 2',
                'unit_type' => 'rv',
                'check_in' => sprintf('%04d-%02d-20', $year, $month),
                'check_out' => sprintf('%04d-%02d-22', $year, $month),
                'total_amount' => 1000.00,
                'status' => 'checked_in',
            ],
            [
                'guest_name' => 'Brown, Laura',
                'guest_phone' => '+52 55 4321 8765',
                'unit_name' => 'Camping 2',
                'unit_type' => 'camping',
                'check_in' => sprintf('%04d-%02d-20', $year, $month),
                'check_out' => sprintf('%04d-%02d-21', $year, $month),
                'total_amount' => 200.00,
                'status' => 'confirmed',
            ],
            [
                'guest_name' => 'Wilson, David',
                'guest_phone' => '+1 312 555 8923',
                'unit_name' => 'Bungalow Superior A3',
                'unit_type' => 'bungalow',
                'check_in' => sprintf('%04d-%02d-20', $year, $month),
                'check_out' => sprintf('%04d-%02d-25', $year, $month),
                'total_amount' => 5000.00,
                'status' => 'pending',
            ],

            // Other events throughout the month
            [
                'guest_name' => 'Familia Johnson',
                'guest_phone' => '+1 650 555 9210',
                'unit_name' => 'Bungalow Superior A1',
                'unit_type' => 'bungalow',
                'check_in' => sprintf('%04d-%02d-05', $year, $month),
                'check_out' => sprintf('%04d-%02d-08', $year, $month),
                'total_amount' => 3000.00,
                'status' => 'checked_out',
            ],
            [
                'guest_name' => 'Gómez, Ricardo',
                'guest_phone' => '+52 55 9988 7766',
                'unit_name' => 'RV 1',
                'unit_type' => 'rv',
                'check_in' => sprintf('%04d-%02d-06', $year, $month),
                'check_out' => sprintf('%04d-%02d-10', $year, $month),
                'total_amount' => 2000.00,
                'status' => 'checked_out',
            ],
            [
                'guest_name' => 'Miller, Sarah',
                'guest_phone' => '+1 206 555 0145',
                'unit_name' => 'Camping 1',
                'unit_type' => 'camping',
                'check_in' => sprintf('%04d-%02d-12', $year, $month),
                'check_out' => sprintf('%04d-%02d-14', $year, $month),
                'total_amount' => 400.00,
                'status' => 'checked_out',
            ],
            [
                'guest_name' => 'López, María',
                'guest_phone' => '+52 81 9900 1122',
                'unit_name' => 'Bungalow Superior A2',
                'unit_type' => 'bungalow',
                'check_in' => sprintf('%04d-%02d-15', $year, $month),
                'check_out' => sprintf('%04d-%02d-18', $year, $month),
                'total_amount' => 3000.00,
                'status' => 'confirmed',
            ],
            [
                'guest_name' => 'Davis, Robert',
                'guest_phone' => '+1 503 555 0167',
                'unit_name' => 'RV 2',
                'unit_type' => 'rv',
                'check_in' => sprintf('%04d-%02d-24', $year, $month),
                'check_out' => sprintf('%04d-%02d-28', $year, $month),
                'total_amount' => 2000.00,
                'status' => 'confirmed',
            ],
        ];

        foreach ($mockReservations as $idx => $m) {
            // Avoid duplication if database already has mock data (check by title and start)
            $exists = false;
            foreach ($events as $e) {
                if ($e['start'] === $m['check_in'] && str_contains($e['title'], $m['guest_name'])) {
                    $exists = true;
                    break;
                }
            }
            if ($exists) continue;

            $events[] = [
                'id' => 'mock_' . $idx,
                'title' => $m['guest_name'],
                'start' => $m['check_in'],
                'end' => $m['check_out'],
                'allDay' => true,
                'extendedProps' => [
                    'is_mock' => true,
                    'guest_name' => $m['guest_name'],
                    'guest_phone' => $m['guest_phone'],
                    'unit_name' => $m['unit_name'],
                    'unit_type' => $m['unit_type'],
                    'total_amount' => $m['total_amount'],
                    'status' => $m['status'],
                    'check_in' => $m['check_in'],
                    'check_out' => $m['check_out'],
                ]
            ];
        }

        return $events;
    }
}
