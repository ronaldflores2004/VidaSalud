<?php

namespace Database\Seeders;

use App\Models\P2_GestionMedica\Especialidad;
use Illuminate\Database\Seeder;

/**
 * P2 Gestión Médica – especialidades definidas por el grupo (sin terapia intensiva).
 * Ejecutar solo este seeder:  php artisan db:seed --class=P2_EspecialidadSeeder
 */
class P2_EspecialidadSeeder extends Seeder
{
    public function run(): void
    {
        $especialidades = [
            'Medicina general' => 'Atención primaria, control y derivación',
            'Pediatría' => 'Atención de niños y adolescentes',
            'Geriatría' => 'Atención del adulto mayor',
            'Cardiología' => 'Enfermedades del corazón y presión arterial',
            'Ginecología' => 'Salud de la mujer',
            'Traumatología' => 'Lesiones de huesos, músculos y articulaciones',
            'Reumatología' => 'Enfermedades de las articulaciones y autoinmunes',
            'Oftalmología' => 'Salud visual',
            'Otorrinolaringología' => 'Oído, nariz y garganta',
        ];
        foreach ($especialidades as $nombre => $descripcion) {
            Especialidad::updateOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }
    }
}
