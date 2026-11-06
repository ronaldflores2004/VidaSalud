<?php

namespace App\Services\P4_GestionCitas\CU11_ReprogramarCita;

use App\Exceptions\ReglaNegocioException;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P1_SeguridadAcceso\User;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P4_GestionCitas\HistorialCita;
use App\Models\P7_PagosNotificaciones\Notificacion;
use App\Services\P4_GestionCitas\CU9_ConsultarDisponibilidad\DisponibilidadService; // CU11 incluye a CU9
use App\Support\PoliticaCitas;
use Carbon\Carbon;
use App\Services\P7_PagosNotificaciones\CU22_GestionarNotificaciones\NotificacionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Capa de negocio – CU11 Reprogramar cita (RF08).
 * Cambia la fecha y la hora de una cita vigente con el mismo médico, sin cruces (RN07),
 * y deja la fecha y hora anteriores en el historial de la cita.
 */
class ReprogramarCitaService
{
    public function __construct(private DisponibilidadService $disponibilidad)
    {
    }

    public function reprogramar(Cita $cita, string $fecha, string $hora, string $motivo, User $usuario): Cita
    {
        try {
            return DB::transaction(function () use ($cita, $fecha, $hora, $motivo, $usuario) {
                $cita = Cita::whereKey($cita->id)->lockForUpdate()->firstOrFail();
                $medico = Medico::whereKey($cita->medico_id)->lockForUpdate()->firstOrFail();
                $nueva = Carbon::parse($fecha);

                // RN18: hasta N horas antes y como máximo M reprogramaciones
                if ($motivoNo = PoliticaCitas::motivoNoReprogramable($cita)) {
                    throw new ReglaNegocioException($motivoNo);
                }
                if ($usuario->tieneRol('Paciente') && $cita->paciente_id !== $usuario->paciente?->id) {
                    throw new ReglaNegocioException('Solo puede reprogramar sus propias citas.');
                }
                if ($nueva->lt(Carbon::today())) {
                    throw new ReglaNegocioException('No se pueden elegir fechas pasadas (RN06).');
                }
                if ($nueva->toDateString() === $cita->fecha->toDateString() && substr($hora, 0, 5) === substr($cita->hora_inicio, 0, 5)) {
                    throw new ReglaNegocioException('Elija una fecha u hora distinta a la actual.');
                }

                // CU9: el bloque debe estar libre (se ignora la propia cita)
                $bloque = $this->disponibilidad->bloqueLibre($medico, $nueva, $hora, $cita->id);
                if (! $bloque) {
                    throw new ReglaNegocioException('El horario ' . substr($hora, 0, 5) . ' no está disponible para el médico. Elija otro.');
                }

                $anterior = ['fecha' => $cita->fecha->toDateString(), 'hora' => $cita->hora_inicio, 'estado' => $cita->estado];
                $cita->update([
                    'fecha' => $nueva->toDateString(), 'hora_inicio' => $bloque['inicio'], 'hora_fin' => $bloque['fin'],
                    'estado' => Cita::PROGRAMADA,
                ]);

                HistorialCita::create([
                    'cita_id' => $cita->id, 'accion' => 'REPROGRAMADA',
                    'estado_anterior' => $anterior['estado'], 'estado_nuevo' => Cita::PROGRAMADA,
                    'fecha_anterior' => $anterior['fecha'], 'hora_anterior' => $anterior['hora'],
                    'fecha_nueva' => $cita->fecha, 'hora_nueva' => $cita->hora_inicio,
                    'motivo' => $motivo, 'user_id' => $usuario->id,
                ]);

                // El recordatorio pendiente se mueve a la nueva fecha y se avisa al paciente
                Notificacion::where('cita_id', $cita->id)->where('tipo', 'RECORDATORIO')->whereNull('enviada_en')
                    ->update(['programada_para' => $cita->inicio->copy()->subDay()]);
                if ($cita->paciente->acepta_notificaciones) {
                    app(NotificacionService::class)->crear([
                        'cita_id' => $cita->id, 'tipo' => 'REPROGRAMACION',
                        'mensaje' => "Su cita {$cita->codigo} fue reprogramada al " . $cita->fecha->format('d/m/Y') . ' a las ' . substr($cita->hora_inicio, 0, 5) . '.',
                    ]);
                }

                Bitacora::registrar('MODIFICAR', 'P4_GestionCitas', $cita,
                    "Cita {$cita->codigo} reprogramada de {$anterior['fecha']} " . substr($anterior['hora'], 0, 5) . " a {$cita->fecha->toDateString()} " . substr($cita->hora_inicio, 0, 5), $usuario->id);

                return $cita;
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23P01') {          // EXCLUDE: otra reserva ganó el bloque
                throw new ReglaNegocioException('El médico ya tiene una cita en ese horario (RN07).');
            }
            throw $e;
        }
    }
}
