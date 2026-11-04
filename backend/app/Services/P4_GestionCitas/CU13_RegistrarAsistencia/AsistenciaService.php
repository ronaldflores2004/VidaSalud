<?php

namespace App\Services\P4_GestionCitas\CU13_RegistrarAsistencia;

use App\Exceptions\ReglaNegocioException;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P1_SeguridadAcceso\User;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P4_GestionCitas\HistorialCita;
use Illuminate\Support\Facades\DB;

/**
 * Capa de negocio – CU13 Registrar asistencia (RF10).
 *  - Llegada: PROGRAMADA → CONFIRMADA (solo el día de la cita).
 *  - Inasistencia: PROGRAMADA → INASISTENCIA (solo cuando ya pasó la hora de inicio).
 */
class AsistenciaService
{
    public function registrar(Cita $cita, string $tipo, User $usuario): Cita
    {
        return DB::transaction(function () use ($cita, $tipo, $usuario) {
            $cita = Cita::whereKey($cita->id)->lockForUpdate()->firstOrFail();

            if ($cita->estado !== Cita::PROGRAMADA) {
                throw new ReglaNegocioException('Solo se registra la asistencia de citas programadas (estado actual: ' . strtolower($cita->estado) . ').');
            }
            if (! $cita->fecha->isToday()) {
                throw new ReglaNegocioException('La asistencia solo se registra el día de la cita.');
            }

            if ($tipo === 'LLEGADA') {
                $nuevo = Cita::CONFIRMADA;
                $accion = 'CONFIRMADA';
            } else {
                if ($cita->inicio->gt(now())) {
                    throw new ReglaNegocioException('Todavía no llegó la hora de la cita; no se puede marcar inasistencia.');
                }
                $nuevo = Cita::INASISTENCIA;
                $accion = 'INASISTENCIA';
            }

            $cita->update(['estado' => $nuevo]);
            HistorialCita::create([
                'cita_id' => $cita->id, 'accion' => $accion, 'estado_anterior' => Cita::PROGRAMADA,
                'estado_nuevo' => $nuevo, 'fecha_nueva' => $cita->fecha, 'hora_nueva' => $cita->hora_inicio,
                'user_id' => $usuario->id,
            ]);
            Bitacora::registrar('MODIFICAR', 'P4_GestionCitas', $cita, "Asistencia de la cita {$cita->codigo}: {$accion}", $usuario->id);

            return $cita;
        });
    }
}
