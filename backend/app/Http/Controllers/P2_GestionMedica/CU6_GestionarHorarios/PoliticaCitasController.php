<?php

namespace App\Http\Controllers\P2_GestionMedica\CU6_GestionarHorarios;

use App\Http\Controllers\Controller;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P2_GestionMedica\Configuracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU6 Gestionar horarios de atención – «Política de citas»: el administrador ajusta los plazos de
 * la clínica (RN17 – RN20) sin modificar el código. La duración de la ficha (15 min) es un
 * requisito fijo del enunciado y no se edita aquí.
 */
class PoliticaCitasController extends Controller
{
    /** clave => [etiqueta, unidad, mínimo, máximo] */
    public const CAMPOS = [
        'CANCELACION_REEMBOLSO_HORAS' => ['Cancelar con reembolso hasta', 'horas antes (RN17)', 0, 168],
        'REPROGRAMACION_MIN_HORAS' => ['Reprogramar hasta', 'horas antes (RN18)', 0, 72],
        'REPROGRAMACION_MAXIMA' => ['Reprogramaciones por cita', 'veces como máximo (RN18)', 0, 10],
        'TOLERANCIA_LLEGADA_MIN' => ['Tolerancia de llegada', 'minutos (RN19)', 0, 60],
        'INASISTENCIAS_LIMITE' => ['Bloquear la reserva web con', 'inasistencias (RN20)', 1, 20],
        'INASISTENCIAS_DIAS' => ['Contar inasistencias de los últimos', 'días (RN20)', 7, 365],
        'TIPO_CAMBIO_USD' => ['Tipo de cambio para PayPal', 'Bs por dólar (CU21)', 1, 50],
    ];

    public function index(): View
    {
        return view('P2_GestionMedica.CU6_GestionarHorarios.politica', [
            'campos' => self::CAMPOS,
            'valores' => Configuracion::whereIn('clave', array_keys(self::CAMPOS))->pluck('valor', 'clave'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $reglas = [];
        foreach (self::CAMPOS as $clave => [$etiqueta, , $min, $max]) {
            $reglas[$clave] = ['required', $clave === 'TIPO_CAMBIO_USD' ? 'numeric' : 'integer', "min:$min", "max:$max"];
        }
        $datos = $request->validate($reglas, [], array_map(fn ($c) => mb_strtolower($c[0]), self::CAMPOS));

        foreach ($datos as $clave => $valor) {
            Configuracion::guardar($clave, (string) $valor);
        }
        Bitacora::registrar('MODIFICAR', 'P2_GestionMedica', null, 'Política de citas actualizada', $request->user()->id);

        return back()->with('ok', 'Política de citas actualizada. Se aplica desde ahora a todas las citas.');
    }
}
