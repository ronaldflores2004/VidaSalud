<?php

namespace App\Http\Controllers\P4_GestionCitas\CU13_RegistrarAsistencia;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P4_GestionCitas\Cita;
use App\Services\P4_GestionCitas\CU13_RegistrarAsistencia\AsistenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU13 Registrar asistencia: la recepcionista ve las citas del día y marca la llegada
 * del paciente (CONFIRMADA) o su inasistencia (INASISTENCIA).
 */
class AsistenciaController extends Controller
{
    public function __construct(private AsistenciaService $servicio)
    {
    }

    public function index(Request $request): View
    {
        $citas = Cita::with('paciente', 'medico', 'especialidad', 'pago')
            ->whereDate('fecha', today())
            ->when($request->medico, fn ($q, $m) => $q->where('medico_id', $m))
            ->orderBy('hora_inicio')->get();

        return view('P4_GestionCitas.CU13_RegistrarAsistencia.index', [
            'citas' => $citas,
            'medicos' => Medico::where('activo', true)->orderBy('apellidos')->get(),
            'resumen' => $citas->countBy('estado'),
        ]);
    }

    public function store(Request $request, Cita $cita): RedirectResponse
    {
        $datos = $request->validate(['tipo' => ['required', 'in:LLEGADA,INASISTENCIA']]);

        try {
            $this->servicio->registrar($cita, $datos['tipo'], $request->user());
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', $datos['tipo'] === 'LLEGADA'
            ? "Llegada registrada: {$cita->paciente->nombre_completo} ({$cita->codigo})."
            : "Inasistencia registrada para la cita {$cita->codigo}.");
    }
}
