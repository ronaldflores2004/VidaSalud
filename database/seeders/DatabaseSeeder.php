<?php

namespace Database\Seeders;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos iniciales de demostración (ficticios).
 * Usuarios de prueba (contraseña para todos: Clinica2026):
 *   admin / recepcion / dr.perez
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect([
            Rol::ADMINISTRADOR => 'Configura y supervisa el sistema',
            Rol::RECEPCIONISTA => 'Gestiona pacientes y citas',
            Rol::MEDICO => 'Consulta su agenda de atención',
            Rol::PACIENTE => 'Gestiona su perfil y sus citas',
        ])->map(fn ($desc, $nombre) => Rol::create(['nombre' => $nombre, 'descripcion' => $desc]));

        $clave = 'Clinica2026';
        $admin = User::create(['name' => 'Ana Gutiérrez Rojas', 'username' => 'admin', 'email' => 'admin@vidasalud.bo', 'password' => $clave, 'rol_id' => $roles[Rol::ADMINISTRADOR]->id]);
        User::create(['name' => 'Carla Méndez Suárez', 'username' => 'recepcion', 'email' => 'recepcion@vidasalud.bo', 'password' => $clave, 'rol_id' => $roles[Rol::RECEPCIONISTA]->id]);
        $uPerez = User::create(['name' => 'Juan Pérez Vargas', 'username' => 'dr.perez', 'email' => 'jperez@vidasalud.bo', 'password' => $clave, 'rol_id' => $roles[Rol::MEDICO]->id]);

        $esp = collect([
            'Medicina general' => 'Atención primaria y diagnóstico general',
            'Pediatría' => 'Atención de niños y adolescentes',
            'Ginecología' => 'Salud de la mujer',
            'Odontología' => 'Salud bucal',
            'Cardiología' => 'Enfermedades del corazón',
        ])->map(fn ($d, $n) => Especialidad::create(['nombre' => $n, 'descripcion' => $d]));

        $medicos = [
            ['Juan', 'Pérez Vargas', 'CMB-10234', ['Medicina general'], $uPerez->id, [[1, '08:00', '12:00'], [3, '14:00', '18:00'], [5, '08:00', '12:00']]],
            ['Lucía', 'Fernández Arce', 'CMB-11870', ['Pediatría'], null, [[2, '09:00', '13:00'], [4, '09:00', '13:00']]],
            ['Roberto', 'Salvatierra Chávez', 'CMB-09455', ['Cardiología', 'Medicina general'], null, [[1, '14:00', '18:00'], [4, '14:00', '18:00']]],
            ['María José', 'Antelo Rivero', 'CMB-12011', ['Ginecología'], null, [[2, '14:00', '18:00'], [5, '14:00', '18:00']]],
            ['Diego', 'Rocha Justiniano', 'COB-03321', ['Odontología'], null, [[1, '08:00', '12:00'], [3, '08:00', '12:00'], [6, '08:00', '12:00']]],
        ];
        foreach ($medicos as $i => [$n, $a, $reg, $espNombres, $userId, $horarios]) {
            $m = Medico::create([
                'codigo' => sprintf('MED-%04d', $i + 1), 'registro_profesional' => $reg, 'nombres' => $n, 'apellidos' => $a,
                'telefono' => '7' . rand(1000000, 9999999), 'email' => null, 'user_id' => $userId,
            ]);
            $m->especialidades()->sync($esp->only($espNombres)->pluck('id'));
            foreach ($horarios as [$dia, $ini, $fin]) {
                $m->horarios()->create(['dia_semana' => $dia, 'hora_inicio' => $ini, 'hora_fin' => $fin, 'duracion_cita' => 30]);
            }
        }

        $pacientes = [
            ['8456123', 'María', 'López Saavedra', '1990-03-14', 'F', '76543210', 'O+'],
            ['9123456', 'Carlos', 'Ribera Montaño', '1985-11-02', 'M', '71234567', 'A+'],
            ['7788990', 'Sofía', 'Vaca Díez', '2016-06-21', 'F', '78881234', 'B+'],
            ['6655443', 'Jorge', 'Céspedes Rojas', '1972-01-30', 'M', '69012345', 'O-'],
            ['10223344', 'Valeria', 'Ortiz Paz', '1998-09-09', 'F', '75566778', null],
        ];
        foreach ($pacientes as $i => [$ci, $n, $a, $fn, $s, $tel, $sangre]) {
            Paciente::create([
                'codigo' => sprintf('PAC-%04d', $i + 1), 'ci' => $ci, 'nombres' => $n, 'apellidos' => $a, 'fecha_nacimiento' => $fn,
                'sexo' => $s, 'telefono' => $tel, 'tipo_sangre' => $sangre, 'direccion' => 'Santa Cruz de la Sierra',
            ]);
        }

        // Algunas citas de ejemplo en los próximos días hábiles del Dr. Pérez
        $perez = Medico::where('registro_profesional', 'CMB-10234')->first();
        $fecha = today()->addDay();
        while (! in_array($fecha->dayOfWeekIso, [1, 3, 5])) {
            $fecha->addDay();
        }
        $base = $fecha->dayOfWeekIso === 3 ? 14 : 8;
        foreach ([[1, 0, 'Control general'], [2, 1, 'Dolor de cabeza frecuente'], [4, 3, 'Chequeo de presión arterial']] as $k => [$pac, $slot, $motivo]) {
            $ini = sprintf('%02d:%02d', $base + intdiv($slot * 30, 60), ($slot * 30) % 60);
            $fin = sprintf('%02d:%02d', $base + intdiv(($slot + 1) * 30, 60), (($slot + 1) * 30) % 60);
            Cita::create([
                'codigo' => sprintf('CM-%04d', $k + 1), 'paciente_id' => $pac, 'medico_id' => $perez->id,
                'especialidad_id' => $esp['Medicina general']->id, 'fecha' => $fecha->toDateString(),
                'hora_inicio' => $ini, 'hora_fin' => $fin, 'motivo' => $motivo, 'estado' => Cita::PROGRAMADA, 'registrado_por' => $admin->id,
            ]);
        }
    }
}
