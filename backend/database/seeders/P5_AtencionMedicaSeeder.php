<?php

namespace Database\Seeders;

use App\Models\P1_SeguridadAcceso\User;
use App\Models\P2_GestionMedica\BloqueoAgenda;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P3_GestionPacientes\Paciente;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P4_GestionCitas\HistorialCita;
use App\Models\P7_PagosNotificaciones\Notificacion;
use App\Models\P5_AtencionMedica\CatalogoEstudio;
use App\Models\P5_AtencionMedica\Consulta;
use App\Models\P5_AtencionMedica\Estudio;
use App\Models\P5_AtencionMedica\Medicamento;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * P5 Atención Médica – historial de ejemplo de las últimas 3 semanas:
 * citas pasadas de todos los médicos (atendidas, canceladas e inasistencias),
 * con su consulta, tratamiento y estudios. Sirve para probar CU14 a CU20.
 * Ejecutar solo este seeder:  php artisan db:seed --class=P5_AtencionMedicaSeeder
 */
class P5_AtencionMedicaSeeder extends Seeder
{
    private const DIAGNOSTICOS = [
        'Medicina general' => ['Resfrío común', 'Gastritis aguda', 'Cefalea tensional', 'Infección urinaria baja'],
        'Pediatría' => ['Faringitis viral', 'Control de niño sano sin hallazgos', 'Diarrea aguda leve'],
        'Cardiología' => ['Hipertensión arterial grado 1', 'Palpitaciones en estudio'],
        'Ginecología' => ['Control ginecológico anual normal', 'Vaginosis bacteriana'],
        'Geriatría' => ['Artrosis de rodilla', 'Control de diabetes tipo 2'],
        'Traumatología' => ['Esguince de tobillo grado I', 'Lumbalgia mecánica'],
        'Reumatología' => ['Artritis reumatoide en control'],
        'Oftalmología' => ['Conjuntivitis alérgica', 'Miopía leve'],
        'Otorrinolaringología' => ['Otitis media aguda', 'Rinitis alérgica'],
    ];

    public function run(): void
    {
        $this->callOnce([P2_MedicoSeeder::class, P2_BloqueoAgendaSeeder::class, P3_PacienteSeeder::class, P4_CitaSeeder::class, P5_MedicamentoSeeder::class, P5_CatalogoEstudioSeeder::class]);
        mt_srand(2026);                                    // datos de ejemplo siempre iguales

        $admin = User::where('username', 'admin')->value('id');
        $pacientes = Paciente::orderBy('id')->pluck('id')->all();
        $medicamentos = Medicamento::pluck('id')->all();
        $estudios = CatalogoEstudio::pluck('id')->all();

        foreach (Medico::with('horarios', 'especialidades', 'usuario')->orderBy('id')->get() as $medico) {
            $especialidad = $medico->especialidades->first();
            for ($d = Carbon::today()->subDays(21); $d->lt(Carbon::today()); $d->addDay()) {
                if (BloqueoAgenda::queAfectan($medico->id, $d->toDateString())->exists()) {
                    continue;                              // feriados y bloqueos: no hay citas
                }
                foreach ($medico->horarios->where('dia_semana', $d->dayOfWeekIso)->take(1) as $h) {
                    foreach ([0, 30] as $min) {                // 2 fichas por bloque de atención
                        if (mt_rand(1, 100) > 70) {
                            continue;
                        }
                        $inicio = Carbon::parse($d->toDateString() . ' ' . $h->hora_inicio)->addMinutes($min);
                        $estado = ($r = mt_rand(1, 100)) <= 70 ? Cita::ATENDIDA : ($r <= 85 ? Cita::CANCELADA : Cita::INASISTENCIA);
                        $paciente = $pacientes[mt_rand(0, count($pacientes) - 1)];
                        $this->cita($medico, $especialidad->id, $paciente, $inicio, $estado, $admin, $medicamentos, $estudios, $especialidad->nombre);
                    }
                }
            }
        }

        // Consulta completa de María López con el Dr. Pérez: 2 medicamentos y 2 estudios (uno con resultado)
        $perez = Medico::where('registro_profesional', 'CMB-10234')->first();
        $maria = Paciente::where('ci', '8456123')->value('id');
        $dia = Carbon::today()->subDays(7);
        while (! in_array($dia->dayOfWeekIso, [1, 2, 3, 4, 5]) || BloqueoAgenda::queAfectan($perez->id, $dia->toDateString())->exists()) {
            $dia->subDay();
        }
        $cita = $this->cita($perez, $perez->especialidades->first()->id, $maria, Carbon::parse($dia->toDateString() . ' 10:00'),
            Cita::ATENDIDA, $admin, [], [], 'Medicina general', conDetalle: false);
        if (! $cita->consulta) {
            $consulta = Consulta::create([
                'cita_id' => $cita->id, 'fecha_hora' => Carbon::parse($dia->toDateString() . ' 10:05'),
                'motivo_consulta' => 'Control general y cansancio', 'peso_kg' => 62.5, 'talla_cm' => 160, 'presion_arterial' => '115/75',
                'temperatura_c' => 36.6, 'frecuencia_cardiaca' => 76, 'examen_fisico' => 'Paciente lúcida, mucosas levemente pálidas.',
                'diagnostico' => 'Anemia leve a confirmar; gastritis aguda', 'observaciones' => 'Control en 15 días con resultados.',
            ]);
            $consulta->tratamientos()->createMany([
                ['medicamento_id' => Medicamento::where('nombre', 'Omeprazol')->value('id'), 'dosis' => '1 cápsula', 'frecuencia' => 'cada 24 horas', 'duracion' => '14 días', 'indicaciones' => 'En ayunas'],
                ['medicamento_id' => Medicamento::where('nombre', 'Paracetamol')->value('id'), 'dosis' => '1 tableta', 'frecuencia' => 'cada 8 horas', 'duracion' => '3 días', 'indicaciones' => 'Solo si hay dolor'],
            ]);
            $hemo = $consulta->estudios()->create([
                'catalogo_estudio_id' => CatalogoEstudio::where('nombre', 'Hemograma completo')->value('id'), 'indicaciones' => 'En ayunas',
                'estado' => Estudio::ENTREGADO, 'fecha_solicitud' => $dia->toDateString(), 'fecha_resultado' => $dia->copy()->addDays(2)->toDateString(),
                'resultado' => 'Hemoglobina 11,2 g/dL (levemente baja). Resto de valores normales.',
            ]);
            $consulta->estudios()->create([
                'catalogo_estudio_id' => CatalogoEstudio::where('nombre', 'Ecografía abdominal')->value('id'),
                'estado' => Estudio::SOLICITADO, 'fecha_solicitud' => $dia->toDateString(),
            ]);
            Notificacion::create(['estudio_id' => $hemo->id, 'tipo' => 'RESULTADO', 'programada_para' => $dia->copy()->addDays(2)->setTime(9, 0),
                'mensaje' => 'El resultado de su estudio «Hemograma completo» ya está disponible en su historial.']);
        }
    }

    private function cita(Medico $medico, int $especialidadId, int $pacienteId, Carbon $inicio, string $estado, int $admin,
                          array $medicamentos, array $estudios, string $especialidad, bool $conDetalle = true): Cita
    {
        $cita = Cita::firstOrCreate(
            ['medico_id' => $medico->id, 'fecha' => $inicio->toDateString(), 'hora_inicio' => $inicio->format('H:i:s')],
            ['codigo' => Cita::siguienteCodigo(), 'paciente_id' => $pacienteId, 'especialidad_id' => $especialidadId,
             'hora_fin' => $inicio->copy()->addMinutes(15)->format('H:i'), 'motivo' => 'Consulta de ' . mb_strtolower($especialidad),
             'estado' => $estado, 'registrado_por' => $admin]
        );
        if (! $cita->wasRecentlyCreated) {
            return $cita;
        }
        HistorialCita::create(['cita_id' => $cita->id, 'accion' => 'CREADA', 'estado_nuevo' => Cita::PROGRAMADA,
            'fecha_nueva' => $cita->fecha, 'hora_nueva' => $cita->hora_inicio, 'user_id' => $admin, 'created_at' => $inicio->copy()->subDays(3)]);
        $quien = $estado === Cita::ATENDIDA ? ($medico->user_id ?? $admin) : $admin;
        HistorialCita::create(['cita_id' => $cita->id, 'accion' => $estado, 'estado_anterior' => Cita::PROGRAMADA, 'estado_nuevo' => $estado,
            'fecha_anterior' => $estado === Cita::CANCELADA ? $cita->fecha : null, 'hora_anterior' => $estado === Cita::CANCELADA ? $cita->hora_inicio : null,
            'motivo' => $estado === Cita::CANCELADA ? 'El paciente avisó que no podrá asistir' : null, 'user_id' => $quien,
            'created_at' => $estado === Cita::CANCELADA ? $inicio->copy()->subDay() : $inicio]);

        if ($estado === Cita::ATENDIDA && $conDetalle) {
            $opciones = self::DIAGNOSTICOS[$especialidad] ?? ['Consulta sin hallazgos relevantes'];
            $consulta = Consulta::create([
                'cita_id' => $cita->id, 'fecha_hora' => $inicio->copy()->addMinutes(3), 'motivo_consulta' => $cita->motivo,
                'peso_kg' => mt_rand(500, 900) / 10, 'talla_cm' => mt_rand(1500, 1850) / 10, 'presion_arterial' => mt_rand(105, 135) . '/' . mt_rand(65, 85),
                'temperatura_c' => mt_rand(360, 378) / 10, 'frecuencia_cardiaca' => mt_rand(62, 96),
                'diagnostico' => $opciones[mt_rand(0, count($opciones) - 1)],
            ]);
            if (mt_rand(1, 100) <= 70) {
                $consulta->tratamientos()->create(['medicamento_id' => $medicamentos[mt_rand(0, count($medicamentos) - 1)],
                    'dosis' => '1 tableta', 'frecuencia' => 'cada 8 horas', 'duracion' => mt_rand(3, 10) . ' días']);
            }
            if (mt_rand(1, 100) <= 35) {
                $conResultado = mt_rand(0, 1) === 1;
                $consulta->estudios()->create(['catalogo_estudio_id' => $estudios[mt_rand(0, count($estudios) - 1)],
                    'estado' => $conResultado ? Estudio::REALIZADO : Estudio::SOLICITADO, 'fecha_solicitud' => $inicio->toDateString(),
                    'fecha_resultado' => $conResultado ? $inicio->copy()->addDay()->toDateString() : null,
                    'resultado' => $conResultado ? 'Valores dentro de parámetros normales.' : null]);
            }
        }

        return $cita;
    }
}
