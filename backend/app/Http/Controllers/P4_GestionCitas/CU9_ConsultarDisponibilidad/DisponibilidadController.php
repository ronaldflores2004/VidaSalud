<?php

namespace App\Http\Controllers\P4_GestionCitas\CU9_ConsultarDisponibilidad;

use App\Http\Controllers\Controller;
use App\Models\P2_GestionMedica\Especialidad;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P3_GestionPacientes\Paciente;
use App\Services\P4_GestionCitas\CU9_ConsultarDisponibilidad\DisponibilidadService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU9 Consultar disponibilidad. Endpoints JSON que usa el formulario de citas (fetch en JavaScript).
 */
class DisponibilidadController extends Controller
{
    public function __construct(private DisponibilidadService $disponibilidad)
    {
    }

    /** Médicos activos que atienden una especialidad. */
    public function medicos(Especialidad $especialidad): JsonResponse
    {
        $medicos = $especialidad->medicos()->where('activo', true)
            ->withCount(['horarios' => fn ($h) => $h->where('activo', true)])
            ->orderBy('apellidos')->get()
            ->map(fn (Medico $m) => [
                'id' => $m->id,
                'nombre' => $m->nombre_completo,
                'dias' => $m->horarios()->where('activo', true)->get()->pluck('nombre_dia')->unique()->values(),
            ]);

        return response()->json($medicos);
    }

    /** Bloques de horario de un médico para una fecha. */
    public function bloques(Request $request, Medico $medico): JsonResponse
    {
        $request->validate(['fecha' => ['required', 'date_format:Y-m-d']]);
        $fecha = Carbon::parse($request->fecha);

        if ($fecha->lt(Carbon::today())) {
            return response()->json(['bloques' => [], 'mensaje' => 'No se pueden elegir fechas pasadas.']);
        }

        $bloques = $this->disponibilidad->bloques($medico, $fecha);

        return response()->json([
            'bloques' => $bloques,
            'mensaje' => $bloques->isEmpty()
                ? 'El médico no atiende el día ' . $fecha->locale('es')->isoFormat('dddd D [de] MMMM') . '.'
                : null,
        ]);
    }

    /** Búsqueda rápida de pacientes activos para el formulario de cita. */
    public function pacientes(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:60']]);

        return response()->json(
            Paciente::where('activo', true)->buscar($request->q)->orderBy('apellidos')->limit(8)->get()
                ->map(fn ($p) => ['id' => $p->id, 'codigo' => $p->codigo, 'ci' => $p->ci, 'nombre' => $p->nombre_completo])
        );
    }
}
