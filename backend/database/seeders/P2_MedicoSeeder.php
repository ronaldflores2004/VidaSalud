<?php

namespace Database\Seeders;

use App\Models\P1_SeguridadAcceso\User;
use App\Models\P2_GestionMedica\Especialidad;
use App\Models\P2_GestionMedica\Medico;
use Illuminate\Database\Seeder;

/**
 * P2 Gestión Médica – médicos con sus especialidades y horarios semanales de atención.
 * Los horarios se dividen en fichas de 15 minutos (tabla configuracion, DURACION_FICHA_MIN).
 * Ejecutar solo este seeder:  php artisan db:seed --class=P2_MedicoSeeder
 */
class P2_MedicoSeeder extends Seeder
{
    public function run(): void
    {
        $this->callOnce([P1_UsuarioSeeder::class, P2_EspecialidadSeeder::class]);
        $especialidades = Especialidad::pluck('id', 'nombre');

        // [nombres, apellidos, registro profesional, especialidades, usuario, horarios [día (1=lunes), inicio, fin]]
        $medicos = [
            ['Juan', 'Pérez Vargas', 'CMB-10234', ['Medicina general'], 'dr.perez', [[1, '08:00', '12:00'], [3, '14:00', '18:00'], [5, '08:00', '12:00']]],
            ['Lucía', 'Fernández Arce', 'CMB-11870', ['Pediatría'], null, [[2, '09:00', '12:00'], [4, '09:00', '12:00']]],
            ['Roberto', 'Salvatierra Chávez', 'CMB-09455', ['Cardiología', 'Medicina general'], null, [[1, '14:00', '18:00'], [4, '14:00', '18:00']]],
            ['María José', 'Antelo Rivero', 'CMB-12011', ['Ginecología'], null, [[2, '14:00', '18:00'], [5, '14:00', '18:00']]],
            ['Hugo', 'Montero Suárez', 'CMB-08712', ['Geriatría'], null, [[3, '08:00', '12:00']]],
            ['Daniela', 'Rojas Paz', 'CMB-13377', ['Traumatología'], null, [[2, '08:00', '12:00'], [6, '08:00', '11:00']]],
            ['Fernando', 'Cuéllar Áñez', 'CMB-10990', ['Reumatología'], null, [[4, '08:00', '11:00']]],
            ['Patricia', 'Méndez Ortiz', 'CMB-12560', ['Oftalmología'], null, [[1, '09:00', '12:00'], [5, '14:00', '17:00']]],
            ['Gabriel', 'Suárez Ribera', 'CMB-11421', ['Otorrinolaringología'], null, [[3, '14:00', '17:00']]],
        ];
        foreach ($medicos as $i => [$nombres, $apellidos, $registro, $esp, $username, $horarios]) {
            $medico = Medico::updateOrCreate(['registro_profesional' => $registro], [
                'codigo' => sprintf('MED-%04d', $i + 1), 'nombres' => $nombres, 'apellidos' => $apellidos,
                'telefono' => '7' . (1000000 + $i * 731117 % 8999999),
                'user_id' => $username ? User::where('username', $username)->value('id') : null,
            ]);
            $medico->especialidades()->syncWithoutDetaching($especialidades->only($esp)->values());
            foreach ($horarios as [$dia, $inicio, $fin]) {
                $medico->horarios()->firstOrCreate(['dia_semana' => $dia, 'hora_inicio' => $inicio], ['hora_fin' => $fin]);
            }
        }
    }
}
