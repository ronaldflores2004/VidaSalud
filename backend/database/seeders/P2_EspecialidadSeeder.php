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
            'Medicina general' => ['Atención primaria, control y derivación', 100],
            'Pediatría' => ['Atención de niños y adolescentes', 150],
            'Geriatría' => ['Atención del adulto mayor', 150],
            'Cardiología' => ['Enfermedades del corazón y presión arterial', 200],
            'Ginecología' => ['Salud de la mujer', 180],
            'Traumatología' => ['Lesiones de huesos, músculos y articulaciones', 180],
            'Reumatología' => ['Enfermedades de las articulaciones y autoinmunes', 200],
            'Oftalmología' => ['Salud visual', 180],
            'Otorrinolaringología' => ['Oído, nariz y garganta', 180],
        ];
        // nombre => [descripción, precio de la ficha en Bs (CU21)]
        foreach ($especialidades as $nombre => [$descripcion, $precio]) {
            Especialidad::updateOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion, 'precio_consulta' => $precio]);
        }
    }
}
