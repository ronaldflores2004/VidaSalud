<?php

namespace Database\Seeders;

use App\Models\P1_SeguridadAcceso\User;
use App\Models\P2_GestionMedica\Especialidad;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P3_GestionPacientes\Paciente;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P4_GestionCitas\HistorialCita;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * P4 Gestión de Citas – 3 citas de ejemplo en el próximo día de atención del Dr. Pérez,
 * cada una con su registro CREADA en historial_citas.
 * Ejecutar solo este seeder:  php artisan db:seed --class=P4_CitaSeeder
 */
class P4_CitaSeeder extends Seeder
{
    public function run(): void
    {
        $this->callOnce([P2_MedicoSeeder::class, P3_PacienteSeeder::class]);

        $admin = User::where('username', 'admin')->value('id');
        $perez = Medico::where('registro_profesional', 'CMB-10234')->firstOrFail();
        $general = Especialidad::where('nombre', 'Medicina general')->value('id');

        // Próximo lunes, miércoles o viernes (días de atención del Dr. Pérez)
        $fecha = today()->addDay();
        while (! in_array($fecha->dayOfWeekIso, [1, 3, 5])) {
            $fecha->addDay();
        }
        $base = Carbon::parse($fecha->toDateString() . ($fecha->dayOfWeekIso === 3 ? ' 14:00' : ' 08:00'));

        // [CI del paciente, número de ficha (0 = primera), motivo]
        $citas = [['8456123', 0, 'Control general'], ['9123456', 1, 'Dolor de cabeza frecuente'], ['6655443', 3, 'Chequeo de presión arterial']];
        foreach ($citas as $k => [$ci, $ficha, $motivo]) {
            $inicio = $base->copy()->addMinutes($ficha * 15);
            $cita = Cita::updateOrCreate(['codigo' => sprintf('CM-%04d', $k + 1)], [
                'paciente_id' => Paciente::where('ci', $ci)->value('id'), 'medico_id' => $perez->id,
                'especialidad_id' => $general, 'fecha' => $fecha->toDateString(),
                'hora_inicio' => $inicio->format('H:i'), 'hora_fin' => $inicio->copy()->addMinutes(15)->format('H:i'),
                'motivo' => $motivo, 'estado' => Cita::PROGRAMADA, 'registrado_por' => $admin,
            ]);
            HistorialCita::firstOrCreate(['cita_id' => $cita->id, 'accion' => 'CREADA'], [
                'estado_nuevo' => Cita::PROGRAMADA, 'fecha_nueva' => $cita->fecha,
                'hora_nueva' => $cita->hora_inicio, 'user_id' => $admin,
            ]);
        }
    }
}
