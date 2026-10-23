<?php

namespace Database\Seeders;

use App\Models\P5_AtencionMedica\CatalogoEstudio;
use Illuminate\Database\Seeder;

/**
 * P5 Atención Médica – catálogo de estudios (se usa en CU18 Gestionar estudios médicos, Ciclo II).
 * Ejecutar solo este seeder:  php artisan db:seed --class=P5_CatalogoEstudioSeeder
 */
class P5_CatalogoEstudioSeeder extends Seeder
{
    public function run(): void
    {
        $estudios = [
            ['Hemograma completo', 'LABORATORIO'], ['Glucosa en ayunas', 'LABORATORIO'], ['Perfil lipídico', 'LABORATORIO'],
            ['Examen general de orina', 'LABORATORIO'], ['Rayos X de tórax', 'IMAGEN'], ['Ecografía abdominal', 'IMAGEN'],
            ['Electrocardiograma', 'OTRO'],
        ];
        foreach ($estudios as [$nombre, $categoria]) {
            CatalogoEstudio::updateOrCreate(['nombre' => $nombre], ['categoria' => $categoria]);
        }
    }
}
