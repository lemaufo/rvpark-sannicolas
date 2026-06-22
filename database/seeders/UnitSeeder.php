<?php

namespace Database\Seeders;
use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Crear tres bungalows
        Unit::create([
            'name'=>'Bungalow Superior A1',
            'type'=>'bungalow',
            'status'=>'available',
            'notes'=>'Vista panorámica al lago y aire acondicionado nuevo.',
            'price_per_day' => 1500,
            'price_per_hour' => 200,
        ]);
        Unit::create([
            'name'=>'Bungalow Superior A2',
            'type'=>'bungalow',
            'status'=>'available',
            'notes'=>'Cerca de la zona de juegos.',
            'price_per_day' => 1500,
            'price_per_hour' => 200,
        ]);
        Unit::create([
            'name'=>'Bungalow Superior A3',
            'type'=>'bungalow',
            'status'=>'cleaning',
            'notes'=>'Mantenimiento preventivo en el calentador de agua.',
            'price_per_day' => 1500,
            'price_per_hour' => 200,
        ]);
        Unit::create([
            'name' => 'RV 1',
            'type' => 'rv',
            'status' => 'cleaning',
            'notes' => 'Sombra natural por árboles grandes, espacio para toldo.',
            'price_per_day' => 800,
            'price_per_hour' => 100,
        ]);
        Unit::create([
            'name' => 'RV 2',
            'type' => 'rv',
            'status' => 'cleaning',
            'notes' => 'Ubicación tranquila, alejada del ruido de la carretera.',
            'price_per_day' => 800,
            'price_per_hour' => 100,
        ]);
        Unit::create([
            'name' => 'RV 3',
            'type' => 'rv',
            'status' => 'available',
            'notes' => 'Conexiones completas de agua y electricidad 50 AMP.',
            'price_per_day' => 800,
            'price_per_hour' => 100,
        ]);
        Unit::create([
            'name' => 'Camping 1',
            'type' => 'camping',
            'status' => 'available',
            'notes' => 'Mucha sombra, suelo blando para tiendas, fogata permitida.',
            'price_per_day' => 350,
            'price_per_hour' => 50,
        ]);
        Unit::create([
            'name' => 'Camping 2',
            'type' => 'camping',
            'status' => 'available',
            'notes' => 'Acceso directo al sendero del río, terreno plano.',
            'price_per_day' => 350,
            'price_per_hour' => 50,
        ]);

    }
}
