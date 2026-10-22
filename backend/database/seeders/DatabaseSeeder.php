<?php

namespace Database\Seeders;

use App\Models\P2_GestionMedica\BloqueoAgenda;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P4_GestionCitas\HistorialCita;
use App\Models\P2_GestionMedica\Especialidad;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P3_GestionPacientes\Paciente;
use App\Models\P1_SeguridadAcceso\Rol;
use App\Models\P1_SeguridadAcceso\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Datos iniciales de demostración (ficticios).
 * Usuarios de prueba (contraseña para todos: Clinica2026):
 *   admin / recepcion / dr.perez / 8456123 (paciente María López)
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

        // Especialidades definidas por el grupo (sin terapia intensiva)
        $esp = collect([
            'Medicina general' => 'Atención primaria, control y derivación',
            'Pediatría' => 'Atención de niños y adolescentes',
            'Geriatría' => 'Atención del adulto mayor',
            'Cardiología' => 'Enfermedades del corazón y presión arterial',
            'Ginecología' => 'Salud de la mujer',
            'Traumatología' => 'Lesiones de huesos, músculos y articulaciones',
            'Reumatología' => 'Enfermedades de las articulaciones y autoinmunes',
            'Oftalmología' => 'Salud visual',
            'Otorrinolaringología' => 'Oído, nariz y garganta',
        ])->map(fn ($d, $n) => Especialidad::create(['nombre' => $n, 'descripcion' => $d]));

        $medicos = [
            ['Juan', 'Pérez Vargas', 'CMB-10234', ['Medicina general'], $uPerez->id, [[1, '08:00', '12:00'], [3, '14:00', '18:00'], [5, '08:00', '12:00']]],
            ['Lucía', 'Fernández Arce', 'CMB-11870', ['Pediatría'], null, [[2, '09:00', '12:00'], [4, '09:00', '12:00']]],
            ['Roberto', 'Salvatierra Chávez', 'CMB-09455', ['Cardiología', 'Medicina general'], null, [[1, '14:00', '18:00'], [4, '14:00', '18:00']]],
            ['María José', 'Antelo Rivero', 'CMB-12011', ['Ginecología'], null, [[2, '14:00', '18:00'], [5, '14:00', '18:00']]],
            ['Hugo', 'Montero Suárez', 'CMB-08712', ['Geriatría'], null, [[3, '08:00', '12:00']]],
            ['Daniela', 'Rojas Paz', 'CMB-13377', ['Traumatología'], null, [[2, '08:00', '12:00'], [6, '08:00', '11:00']]],
            ['Fernando', 'Cuéllar Áñez', 'CMB-10990', ['Reumatología'], null, [[4, '08:00', '11:00']]],
            ['Patricia', 'Méndez Ortiz', 'CMB-12560', ['Oftalmología'], null, [[1, '09:00', '12:00'], [5, '14:00', '17:00']]],
            ['Gabriel', 'Suárez Ribera', 'CMB-11421', ['Otorrinolaringología'], null, [[3, '14:00', '17:00']]],
        ];
        foreach ($medicos as $i => [$n, $a, $reg, $espNombres, $userId, $horarios]) {
            $m = Medico::create([
                'codigo' => sprintf('MED-%04d', $i + 1), 'registro_profesional' => $reg, 'nombres' => $n, 'apellidos' => $a,
                'telefono' => '7' . (1000000 + $i * 731117 % 8999999), 'email' => null, 'user_id' => $userId,
            ]);
            $m->especialidades()->sync($esp->only($espNombres)->pluck('id'));
            foreach ($horarios as [$dia, $ini, $fin]) {
                // 15 minutos de atención por paciente
                $m->horarios()->create(['dia_semana' => $dia, 'hora_inicio' => $ini, 'hora_fin' => $fin, 'duracion_cita' => 15]);
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

        // Cuenta web de la paciente María López (se registró desde la web): usuario = su CI
        $uMaria = User::create(['name' => 'María López Saavedra', 'username' => '8456123', 'email' => 'maria.lopez@correo.bo', 'password' => $clave, 'rol_id' => $roles[Rol::PACIENTE]->id]);
        Paciente::where('ci', '8456123')->update(['user_id' => $uMaria->id, 'email' => 'maria.lopez@correo.bo']);

        // Algunas citas de ejemplo en los próximos días hábiles del Dr. Pérez
        $perez = Medico::where('registro_profesional', 'CMB-10234')->first();
        $fecha = today()->addDay();
        while (! in_array($fecha->dayOfWeekIso, [1, 3, 5])) {
            $fecha->addDay();
        }
        $base = Carbon::parse($fecha->toDateString() . ($fecha->dayOfWeekIso === 3 ? ' 14:00' : ' 08:00'));
        foreach ([[1, 0, 'Control general'], [2, 1, 'Dolor de cabeza frecuente'], [4, 3, 'Chequeo de presión arterial']] as $k => [$pac, $slot, $motivo]) {
            $ini = $base->copy()->addMinutes($slot * 15);
            $c = Cita::create([
                'codigo' => sprintf('CM-%04d', $k + 1), 'paciente_id' => $pac, 'medico_id' => $perez->id,
                'especialidad_id' => $esp['Medicina general']->id, 'fecha' => $fecha->toDateString(),
                'hora_inicio' => $ini->format('H:i'), 'hora_fin' => $ini->copy()->addMinutes(15)->format('H:i'),
                'motivo' => $motivo, 'estado' => Cita::PROGRAMADA, 'registrado_por' => $admin->id,
            ]);
            HistorialCita::create(['cita_id' => $c->id, 'accion' => 'CREADA', 'estado_nuevo' => Cita::PROGRAMADA,
                'fecha_nueva' => $c->fecha, 'hora_nueva' => $c->hora_inicio, 'user_id' => $admin->id]);
        }

        // Feriado nacional (Día de Todos los Difuntos): bloquea la agenda de toda la clínica
        BloqueoAgenda::create(['medico_id' => null, 'fecha' => '2026-11-02', 'tipo' => 'FERIADO',
            'motivo' => 'Día de Todos los Difuntos', 'registrado_por' => $admin->id]);
    }
}
