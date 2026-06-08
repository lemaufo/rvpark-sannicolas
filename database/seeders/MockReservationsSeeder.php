<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reservation;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MockReservationsSeeder extends Seeder
{
    public function run()
    {
        $units = Unit::all();
        if ($units->isEmpty()) {
            $this->command->error('No units found in the database. Please seed units first.');
            return;
        }

        $names = ['Juan Perez', 'Maria Garcia', 'Carlos Lopez', 'Ana Martinez', 'Pedro Rodriguez', 'Laura Sanchez', 'Diego Fernandez', 'Sofia Gomez', 'Jorge Diaz', 'Carmen Torres', 'Luis Ruiz', 'Marta Alvarez', 'Jose Jimenez', 'Lucia Moreno', 'Miguel Munoz', 'Elena Romero', 'David Alonso', 'Paula Gutierrez', 'Javier Navarro', 'Sara Dominguez'];
        
        $startDates = [
            Carbon::yesterday(),
            Carbon::today(),
            Carbon::tomorrow(),
            Carbon::today()->addDays(3)
        ];

        $statuses = ['pending', 'confirmed', 'checked_in', 'checked_out'];

        for ($i = 0; $i < 20; $i++) {
            $groupIndex = (int) floor($i / 5); // 5 per group
            $baseDate = $startDates[$groupIndex]->copy();
            
            // Add slight variation to check-in (same day or next day)
            $checkIn = $baseDate->copy()->addDays(rand(0, 1));
            
            // Stay length between 5 and 15 days
            $stayLength = rand(5, 15);
            $checkOut = $checkIn->copy()->addDays($stayLength);

            // Random status (make today's ones more likely to be checked_in or confirmed)
            if ($checkIn->isToday()) {
                $status = ['pending', 'confirmed', 'checked_in'][rand(0, 2)];
            } elseif ($checkIn->isPast()) {
                $status = ['checked_in', 'checked_out'][rand(0, 1)];
            } else {
                $status = ['pending', 'confirmed'][rand(0, 1)];
            }

            Reservation::create([
                'unit_id' => $units->random()->id,
                'guest_name' => $names[$i],
                'guest_phone' => '555' . rand(1000000, 9999999),
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $status,
                'total_amount' => rand(500, 5000),
            ]);
        }

        $this->command->info('20 mock reservations created successfully!');
    }
}
