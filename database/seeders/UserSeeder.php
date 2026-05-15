<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1 Admin
        User::updateOrCreate(
            ['email' => 'admin@rvpark.com'],
            [
                'name' => 'Admin Usuario',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2 Receptionists
        User::updateOrCreate(
            ['email' => 'recep1@rvpark.com'],
            [
                'name' => 'Recepcionista 1',
                'password' => Hash::make('password'),
                'role' => 'receptionist',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'recep2@rvpark.com'],
            [
                'name' => 'Recepcionista 2',
                'password' => Hash::make('password'),
                'role' => 'receptionist',
                'email_verified_at' => now(),
            ]
        );
    }
}
