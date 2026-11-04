<?php

namespace App\Http\Controllers\P5_AtencionMedica\CU19_ConsultarHistorialResultados;

use App\Http\Controllers\Controller;
use App\Models\P4_GestionCitas\Notificacion;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU19 Consultar historial y resultados: el paciente ve desde la web su historial clínico,
 * sus consultas con diagnóstico y tratamiento, y los resultados de sus estudios.
 * Solo ve su propia información.
 */
class MiHistorialController extends Controller
{
    public function __invoke(Request $request): View
    {
        $paciente = $request->user()->paciente;
        abort_unless($paciente, 403, 'Su cuenta no está vinculada a un paciente.');

        $consultas = $paciente->consultas()->with('cita.medico', 'cita.especialidad', 'tratamientos.medicamento', 'estudios.catalogo')
            ->latest('fecha_hora')->get();

        // Avisos de sus citas y estudios; al verlos quedan como leídos
        $avisos = Notificacion::where(function ($q) use ($paciente, $consultas) {
            $q->whereHas('cita', fn ($c) => $c->where('paciente_id', $paciente->id))
              ->orWhereIn('estudio_id', $consultas->flatMap->estudios->pluck('id'));
        })->where('programada_para', '<=', now())->latest('programada_para')->limit(10)->get();
        Notificacion::whereIn('id', $avisos->whereNull('leida_en')->pluck('id'))->update(['leida_en' => now()]);

        return view('P5_AtencionMedica.CU19_ConsultarHistorialResultados.index', [
            'paciente' => $paciente,
            'historia' => $paciente->asegurarHistoria(),
            'consultas' => $consultas,
            'estudios' => $consultas->flatMap->estudios->sortByDesc('fecha_solicitud'),
            'avisos' => $avisos,
        ]);
    }
}
