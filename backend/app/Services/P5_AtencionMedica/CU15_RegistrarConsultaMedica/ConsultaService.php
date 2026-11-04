<?php

namespace App\Services\P5_AtencionMedica\CU15_RegistrarConsultaMedica;

use App\Exceptions\ReglaNegocioException;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P1_SeguridadAcceso\User;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P4_GestionCitas\HistorialCita;
use App\Models\P5_AtencionMedica\Consulta;
use App\Models\P5_AtencionMedica\Estudio;
use Illuminate\Support\Facades\DB;

/**
 * Capa de negocio – CU15 Registrar consulta médica.
 *  - <<include>> CU16: la consulta queda en el historial clínico del paciente.
 *  - <<extend>> CU17 y CU18: en la misma consulta se pueden indicar medicamentos y pedir estudios.
 * Al guardar, la cita pasa a ATENDIDA. Todo se hace en una sola transacción.
 */
class ConsultaService
{
    public function puedeAtender(Cita $cita, User $medico): ?string
    {
        if ($cita->medico_id !== $medico->medico?->id) {
            return 'La cita no pertenece a su agenda.';
        }
        if ($cita->consulta()->exists()) {
            return 'La consulta de esta cita ya fue registrada.';
        }
        if (! $cita->estaVigente()) {
            return 'Solo se atienden citas programadas o confirmadas (estado actual: ' . strtolower($cita->estado) . ').';
        }
        if (! $cita->fecha->isToday()) {
            return 'La consulta se registra el día de la cita (' . $cita->fecha->format('d/m/Y') . ').';
        }
        if (! $cita->pago()->exists()) {
            return 'La ficha está pendiente de pago; el paciente debe pagarla en recepción (RN14).';
        }

        return null;
    }

    /**
     * @param  array  $datos  datos de la consulta
     * @param  array<int, array{medicamento_id:int, dosis:string, frecuencia:string, duracion:string, indicaciones:?string}>  $tratamientos
     * @param  array<int, array{catalogo_estudio_id:int, indicaciones:?string}>  $estudios
     */
    public function registrar(Cita $cita, array $datos, array $tratamientos, array $estudios, User $usuario): Consulta
    {
        return DB::transaction(function () use ($cita, $datos, $tratamientos, $estudios, $usuario) {
            $cita = Cita::whereKey($cita->id)->lockForUpdate()->firstOrFail();
            if ($error = $this->puedeAtender($cita, $usuario)) {
                throw new ReglaNegocioException($error);
            }

            $consulta = Consulta::create($datos + ['cita_id' => $cita->id, 'fecha_hora' => now()]);

            foreach ($tratamientos as $t) {                 // CU17
                $consulta->tratamientos()->create($t);
            }
            foreach ($estudios as $e) {                     // CU18
                $consulta->estudios()->create($e + ['estado' => Estudio::SOLICITADO, 'fecha_solicitud' => today()]);
            }

            $anterior = $cita->estado;
            $cita->update(['estado' => Cita::ATENDIDA]);
            HistorialCita::create([
                'cita_id' => $cita->id, 'accion' => 'ATENDIDA', 'estado_anterior' => $anterior, 'estado_nuevo' => Cita::ATENDIDA,
                'fecha_nueva' => $cita->fecha, 'hora_nueva' => $cita->hora_inicio, 'user_id' => $usuario->id,
            ]);

            // CU16: el historial clínico existe para todo paciente
            $cita->paciente->asegurarHistoria();

            Bitacora::registrar('CREAR', 'P5_AtencionMedica', $consulta,
                "Consulta de la cita {$cita->codigo}: " . count($tratamientos) . ' medicamento(s), ' . count($estudios) . ' estudio(s)', $usuario->id);

            return $consulta;
        });
    }
}
