<?php

namespace App\Http\Controllers\P4_GestionCitas\CU14_ConsultarAgendaMedica;

use App\Http\Controllers\Controller;
use App\Models\P4_GestionCitas\Cita;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU14 Consultar agenda médica: el médico ve, día por día, solo sus propias citas
 * y sus pacientes (RF11). Desde aquí inicia la consulta (CU15).
 */
class AgendaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate(['fecha' => ['nullable', 'date_format:Y-m-d']]);
        $medico = $request->user()->medico;
        abort_unless($medico, 403, 'Su cuenta no está vinculada a un médico.');

        $fecha = $request->fecha ? Carbon::parse($request->fecha) : today();
        $citas = Cita::with('paciente', 'especialidad', 'consulta')
            ->where('medico_id', $medico->id)
            ->whereDate('fecha', $fecha)
            ->orderBy('hora_inicio')->get();

        // Resumen de la semana para navegar rápido
        $inicioSemana = $fecha->copy()->startOfWeek();
        $semana = Cita::where('medico_id', $medico->id)
            ->whereBetween('fecha', [$inicioSemana->toDateString(), $inicioSemana->copy()->addDays(6)->toDateString()])
            ->whereIn('estado', Cita::ESTADOS_ACTIVOS)
            ->selectRaw('fecha, COUNT(*) AS total')->groupBy('fecha')->pluck('total', 'fecha')
            ->mapWithKeys(fn ($t, $f) => [Carbon::parse($f)->toDateString() => $t]);

        return view('P4_GestionCitas.CU14_ConsultarAgendaMedica.index', compact('medico', 'fecha', 'citas', 'semana', 'inicioSemana'));
    }
}
