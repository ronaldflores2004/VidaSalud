<?php

namespace App\Services\P4_GestionCitas\CU12_CancelarCita;

use App\Exceptions\ReglaNegocioException;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P1_SeguridadAcceso\User;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P4_GestionCitas\HistorialCita;
use App\Models\P7_PagosNotificaciones\Notificacion;
use App\Models\P7_PagosNotificaciones\Pago;
use App\Services\P7_PagosNotificaciones\CU22_GestionarNotificaciones\NotificacionService;
use App\Support\PoliticaCitas;
use Illuminate\Support\Facades\DB;

/**
 * Capa de negocio – CU12 Cancelar cita (RF09).
 * La cita no se borra: pasa a CANCELADA, libera la ficha (RN09) y guarda el motivo en su historial.
 * Si ya estaba pagada y se cancela con la anticipación de la política (24 h, RN17), el pago queda
 * «por reembolsar» (RN15); si se cancela con menos anticipación, el pago no se devuelve.
 */
class CancelarCitaService
{
    public function cancelar(Cita $cita, string $motivo, User $usuario): Cita
    {
        return DB::transaction(function () use ($cita, $motivo, $usuario) {
            $cita = Cita::whereKey($cita->id)->lockForUpdate()->firstOrFail();

            if (! $cita->estaVigente()) {
                throw new ReglaNegocioException('La cita ya está ' . strtolower($cita->estado) . ' y no se puede cancelar.');
            }
            if ($cita->inicio->lte(now())) {
                throw new ReglaNegocioException('La hora de la cita ya pasó; registre la asistencia o la inasistencia (CU13).');
            }

            $reembolso = PoliticaCitas::cancelacionConReembolso($cita);
            $anterior = $cita->estado;
            $cita->update(['estado' => Cita::CANCELADA]);

            HistorialCita::create([
                'cita_id' => $cita->id, 'accion' => 'CANCELADA', 'estado_anterior' => $anterior,
                'estado_nuevo' => Cita::CANCELADA, 'fecha_anterior' => $cita->fecha, 'hora_anterior' => $cita->hora_inicio,
                'motivo' => $motivo, 'user_id' => $usuario->id,
            ]);

            // El recordatorio ya no se envía; se avisa la cancelación al paciente
            Notificacion::where('cita_id', $cita->id)->where('tipo', 'RECORDATORIO')->whereNull('enviada_en')->delete();
            if ($cita->paciente->acepta_notificaciones && ! $usuario->tieneRol('Paciente')) {
                app(NotificacionService::class)->crear([
                    'cita_id' => $cita->id, 'tipo' => 'CANCELACION',
                    'mensaje' => "Su cita {$cita->codigo} del " . $cita->fecha->format('d/m/Y') . ' fue cancelada. Motivo: ' . mb_strimwidth($motivo, 0, 120, '…'),
                ]);
            }
            if ($cita->medico->user_id) {          // CU22: el médico se entera de que se liberó la ficha
                app(NotificacionService::class)->crear([
                    'cita_id' => $cita->id, 'tipo' => 'CANCELACION', 'para_medico' => true,
                    'mensaje' => "Se canceló la ficha {$cita->codigo} de {$cita->paciente->nombre_completo} (" . $cita->fecha->format('d/m/Y') . ' ' . substr($cita->hora_inicio, 0, 5) . ').',
                ]);
            }

            // RN15 / RN17: reembolso solo si se cancela con la anticipación de la política
            if ($reembolso) {
                Pago::where('cita_id', $cita->id)->update(['estado' => Pago::POR_REEMBOLSAR]);
            }

            Bitacora::registrar('CANCELAR', 'P4_GestionCitas', $cita, "Cita {$cita->codigo} cancelada: {$motivo}", $usuario->id);

            return $cita;
        });
    }
}
