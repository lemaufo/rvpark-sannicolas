<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1 Admin
        User::factory()->admin()->create([
            'name' => 'Admin Usuario',
            'email' => 'admin@rvpark.com',
        ]);

        // 2 Receptionists
        User::factory()->create([
            'name' => 'Recepcionista 1',
            'email' => 'recep1@rvpark.com',
        ]);

        User::factory()->create([
            'name' => 'Recepcionista 2',
            'email' => 'recep2@rvpark.com',
        ]);
    }
}
