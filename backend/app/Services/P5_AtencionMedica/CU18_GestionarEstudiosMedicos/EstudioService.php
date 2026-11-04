<?php

namespace App\Services\P5_AtencionMedica\CU18_GestionarEstudiosMedicos;

use App\Exceptions\ReglaNegocioException;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P1_SeguridadAcceso\User;
use App\Models\P7_PagosNotificaciones\Notificacion;
use App\Models\P5_AtencionMedica\Consulta;
use App\Models\P5_AtencionMedica\Estudio;
use Illuminate\Http\UploadedFile;
use App\Services\P7_PagosNotificaciones\CU22_GestionarNotificaciones\NotificacionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Capa de negocio – CU18 Gestionar estudios médicos.
 * El médico manda uno o varios estudios a un paciente y luego registra su resultado;
 * el paciente recibe un aviso y lo ve en su historial (CU19).
 */
class EstudioService
{
    public function solicitar(Consulta $consulta, int $catalogoId, ?string $indicaciones, User $usuario): Estudio
    {
        if ($consulta->estudios()->where('catalogo_estudio_id', $catalogoId)->where('estado', Estudio::SOLICITADO)->exists()) {
            throw new ReglaNegocioException('Ese estudio ya está solicitado en esta consulta y aún no tiene resultado.');
        }
        $estudio = $consulta->estudios()->create([
            'catalogo_estudio_id' => $catalogoId, 'indicaciones' => $indicaciones,
            'estado' => Estudio::SOLICITADO, 'fecha_solicitud' => today(),
        ]);
        Bitacora::registrar('CREAR', 'P5_AtencionMedica', $estudio, 'Estudio solicitado: ' . $estudio->catalogo->nombre, $usuario->id);

        return $estudio;
    }

    public function registrarResultado(Estudio $estudio, array $datos, ?UploadedFile $archivo, User $usuario): Estudio
    {
        return DB::transaction(function () use ($estudio, $datos, $archivo, $usuario) {
            if ($datos['fecha_resultado'] < $estudio->fecha_solicitud->toDateString()) {
                throw new ReglaNegocioException('La fecha del resultado no puede ser anterior a la fecha de solicitud ('
                    . $estudio->fecha_solicitud->format('d/m/Y') . ').');
            }
            $teniaResultado = $estudio->tieneResultado();

            if ($archivo) {
                if ($estudio->archivo_resultado) {
                    Storage::disk('local')->delete($estudio->archivo_resultado);
                }
                $datos['archivo_resultado'] = $archivo->store('estudios', 'local');   // storage/app/private/estudios
            }
            $estudio->update($datos);

            $paciente = $estudio->consulta->cita->paciente;
            if (! $teniaResultado && $paciente->acepta_notificaciones) {
                app(NotificacionService::class)->crear([
                    'estudio_id' => $estudio->id, 'tipo' => 'RESULTADO',
                    'mensaje' => "El resultado de su estudio «{$estudio->catalogo->nombre}» ya está disponible en su historial.",
                ]);
            }
            Bitacora::registrar('MODIFICAR', 'P5_AtencionMedica', $estudio, 'Resultado registrado: ' . $estudio->catalogo->nombre, $usuario->id);

            return $estudio;
        });
    }
}
