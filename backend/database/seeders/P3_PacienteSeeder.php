<?php

namespace Database\Seeders;

use App\Models\P1_SeguridadAcceso\User;
use App\Models\P3_GestionPacientes\Paciente;
use Illuminate\Database\Seeder;

/**
 * P3 Gestión de Pacientes – pacientes de prueba con su historial clínico (1 a 1)
 * y un contacto de emergencia. María López tiene cuenta web (usuario = su CI 8456123).
 * Ejecutar solo este seeder:  php artisan db:seed --class=P3_PacienteSeeder
 */
class P3_PacienteSeeder extends Seeder
{
    public function run(): void
    {
        $this->callOnce(P1_UsuarioSeeder::class);

        // [CI, nombres, apellidos, nacimiento, sexo, teléfono, tipo de sangre, alergias]
        $pacientes = [
            ['8456123', 'María', 'López Saavedra', '1990-03-14', 'F', '76543210', 'O+', null],
            ['9123456', 'Carlos', 'Ribera Montaño', '1985-11-02', 'M', '71234567', 'A+', 'Penicilina'],
            ['7788990', 'Sofía', 'Vaca Díez', '2016-06-21', 'F', '78881234', 'B+', null],
            ['6655443', 'Jorge', 'Céspedes Rojas', '1972-01-30', 'M', '69012345', 'O-', null],
            ['10223344', 'Valeria', 'Ortiz Paz', '1998-09-09', 'F', '75566778', null, null],
        ];
        foreach ($pacientes as $i => [$ci, $nombres, $apellidos, $nacimiento, $sexo, $telefono, $sangre, $alergias]) {
            $usuario = User::where('username', $ci)->first();   // solo María tiene cuenta web
            $paciente = Paciente::updateOrCreate(['ci' => $ci], [
                'codigo' => sprintf('PAC-%04d', $i + 1), 'nombres' => $nombres, 'apellidos' => $apellidos,
                'fecha_nacimiento' => $nacimiento, 'sexo' => $sexo, 'telefono' => $telefono, 'tipo_sangre' => $sangre,
                'direccion' => 'Santa Cruz de la Sierra', 'email' => $usuario?->email, 'user_id' => $usuario?->id,
            ]);
            $paciente->asegurarHistoria(['alergias' => $alergias]);
            $paciente->contactos()->firstOrCreate(
                ['nombre' => 'Familiar de ' . $nombres],
                ['parentesco' => $sexo === 'F' ? 'Esposo' : 'Esposa', 'telefono' => '7' . (5000000 + $i * 1111)]
            );
        }
    }
}
