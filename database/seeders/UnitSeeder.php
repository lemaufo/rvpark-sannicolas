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
            'notes'=>'Vista panorámica al lago y aire acondicionado nuevo.'
        ]);
        Unit::create([
            'name'=>'Bungalow Superior A2',
            'type'=>'bungalow',
            'status'=>'available',
            'notes'=>'Cerca de la zona de juegos.'
        ]);
        Unit::create([
            'name'=>'Bungalow Superior A3',
            'type'=>'bungalow',
            'status'=>'cleaning',
            'notes'=>'Mantenimiento preventivo en el calentador de agua.'
        ]);
        Unit::create([
            'name' => 'RV 1',
            'type' => 'rv',
            'status' => 'cleaning',
            'notes' => 'Sombra natural por árboles grandes, espacio para toldo.'
        ]);
        Unit::create([
            'name' => 'RV 2',
            'type' => 'rv',
            'status' => 'cleaning',
            'notes' => 'Ubicación tranquila, alejada del ruido de la carretera.'
        ]);
        Unit::create([
            'name' => 'Camping 1',
            'type' => 'camping',
            'status' => 'available',
            'notes' => 'Mucha sombra, suelo blando para tiendas, fogata permitida.'
        ]);
        Unit::create([
            'name' => 'Camping 2',
            'type' => 'camping',
            'status' => 'available',
            'notes' => 'Acceso directo al sendero del río, terreno plano.'
        ]);

    }
}
