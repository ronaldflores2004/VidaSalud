<?php

namespace Database\Seeders;

use App\Models\P5_AtencionMedica\Medicamento;
use Illuminate\Database\Seeder;

/**
 * P5 Atención Médica – catálogo de medicamentos (se usa en CU17 Registrar tratamiento, Ciclo II).
 * Ejecutar solo este seeder:  php artisan db:seed --class=P5_MedicamentoSeeder
 */
class P5_MedicamentoSeeder extends Seeder
{
    public function run(): void
    {
        $medicamentos = [
            ['Paracetamol', 'Tabletas 500 mg'], ['Ibuprofeno', 'Tabletas 400 mg'], ['Amoxicilina', 'Cápsulas 500 mg'],
            ['Losartán', 'Tabletas 50 mg'], ['Omeprazol', 'Cápsulas 20 mg'], ['Salbutamol', 'Inhalador 100 mcg'],
        ];
        foreach ($medicamentos as [$nombre, $presentacion]) {
            Medicamento::firstOrCreate(['nombre' => $nombre, 'presentacion' => $presentacion]);
        }
    }
}
