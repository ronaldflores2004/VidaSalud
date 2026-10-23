<?php

namespace App\Services\P4_GestionCitas\CU10_ReservarFicha;

use App\Exceptions\ReglaNegocioException;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P4_GestionCitas\HistorialCita;
use App\Models\P4_GestionCitas\Notificacion;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P3_GestionPacientes\Paciente;
use App\Models\P1_SeguridadAcceso\User;
use App\Services\P4_GestionCitas\CU9_ConsultarDisponibilidad\DisponibilidadService; // CU10 incluye a CU9
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Capa de negocio – Programación de citas médicas (CU8, RF06, RF07).
 *
 * La disponibilidad se vuelve a comprobar en el servidor dentro de una transacción
 * y con bloqueo de la fila del médico (SELECT ... FOR UPDATE), para que dos
 * recepcionistas no puedan reservar el mismo bloque al mismo tiempo (RN13).
 * Como última barrera, PostgreSQL rechaza los cruces con la restricción EXCLUDE.
 */
class CitaService
{
    public function __construct(private DisponibilidadService $disponibilidad)
    {
    }

    /**
     * @param  array{paciente_id:int, especialidad_id:int, medico_id:int, fecha:string, hora_inicio:string, motivo:?string}  $datos
     */
    public function programar(array $datos, User $usuario): Cita
    {
        try {
            return DB::transaction(function () use ($datos, $usuario) {
                $paciente = Paciente::findOrFail($datos['paciente_id']);
                $medico = Medico::whereKey($datos['medico_id'])->lockForUpdate()->firstOrFail();
                $fecha = Carbon::parse($datos['fecha']);

                if (! $paciente->activo) {
                    throw new ReglaNegocioException('El paciente está inactivo y no puede recibir nuevas citas.');
                }
                if (! $medico->activo) {
                    throw new ReglaNegocioException('El médico seleccionado está inactivo (RN04).');
                }
                if (! $medico->especialidades()->whereKey($datos['especialidad_id'])->exists()) {
                    throw new ReglaNegocioException('El médico no atiende la especialidad seleccionada.');
                }
                if ($fecha->lt(Carbon::today())) {
                    throw new ReglaNegocioException('No se pueden registrar citas en fechas pasadas (RN06).');
                }

                $bloque = $this->disponibilidad->bloqueLibre($medico, $fecha, $datos['hora_inicio']);
                if (! $bloque) {
                    throw new ReglaNegocioException(
                        'El horario ' . substr($datos['hora_inicio'], 0, 5) . ' ya no está disponible o está fuera del horario de atención del médico. Elija otro horario.'
                    );
                }

                $cita = Cita::create([
                    'codigo' => Cita::siguienteCodigo(),
                    'paciente_id' => $paciente->id,
                    'medico_id' => $medico->id,
                    'especialidad_id' => $datos['especialidad_id'],
                    'fecha' => $fecha->toDateString(),
                    'hora_inicio' => $bloque['inicio'],
                    'hora_fin' => $bloque['fin'],
                    'modalidad' => $datos['modalidad'] ?? 'PRESENCIAL',
                    'motivo' => $datos['motivo'] ?? null,
                    'estado' => Cita::PROGRAMADA,
                    'registrado_por' => $usuario->id,
                ]);

                // Trazabilidad: primer registro del historial de la cita (RF12)
                HistorialCita::create([
                    'cita_id' => $cita->id, 'accion' => 'CREADA', 'estado_nuevo' => Cita::PROGRAMADA,
                    'fecha_nueva' => $cita->fecha, 'hora_nueva' => $cita->hora_inicio, 'user_id' => $usuario->id,
                ]);

                // Recordatorio automático 24 horas antes, si el paciente acepta notificaciones
                if ($paciente->acepta_notificaciones) {
                    Notificacion::create([
                        'cita_id' => $cita->id, 'tipo' => 'RECORDATORIO',
                        'mensaje' => "Recuerde su cita {$cita->codigo} el " . $fecha->format('d/m/Y') . " a las {$cita->hora_inicio} con {$medico->nombre_completo}.",
                        'programada_para' => Carbon::parse($fecha->toDateString() . ' ' . $cita->hora_inicio)->subDay(),
                    ]);
                }

                Bitacora::registrar('CREAR', 'P4_GestionCitas', $cita, "Cita {$cita->codigo} para el paciente {$paciente->codigo}", $usuario->id);

                return $cita;
            });
        } catch (QueryException $e) {
            // 23P01 = exclusion_violation: otra transacción ocupó el bloque al mismo tiempo
            if ($e->getCode() === '23P01') {
                throw new ReglaNegocioException('El médico ya tiene una cita en ese horario (RN07).');
            }
            throw $e;
        }
    }
}
