<?php

namespace App\Http\Controllers\P4_GestionCitas\CU12_CancelarCita;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Models\P4_GestionCitas\Cita;
use App\Services\P4_GestionCitas\CU12_CancelarCita\CancelarCitaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU12 Cancelar cita: la recepcionista cancela cualquier cita vigente; el paciente, solo las suyas.
 * La cita no se borra: queda CANCELADA con su motivo y libera la ficha.
 */
class CancelarCitaController extends Controller
{
    public function __construct(private CancelarCitaService $servicio)
    {
    }

    private function autorizar(Request $request, Cita $cita): void
    {
        $u = $request->user();
        abort_if($u->tieneRol('Paciente') && $cita->paciente_id !== $u->paciente?->id, 403, 'Solo puede cancelar sus propias citas.');
    }

    public function create(Request $request, Cita $cita): View|RedirectResponse
    {
        $this->autorizar($request, $cita);
        if (! $cita->estaVigente() || $cita->inicio->lte(now())) {
            return redirect()->route('citas.show', $cita)->with('error', 'Esta cita ya no se puede cancelar.');
        }

        return view('P4_GestionCitas.CU12_CancelarCita.form', ['cita' => $cita->load('paciente', 'medico', 'especialidad')]);
    }

    public function store(Request $request, Cita $cita): RedirectResponse
    {
        $this->autorizar($request, $cita);
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']], [], ['motivo' => 'motivo de la cancelación']);

        try {
            $this->servicio->cancelar($cita, $datos['motivo'], $request->user());
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('citas.show', $cita)->with('ok', "Cita {$cita->codigo} cancelada. La ficha quedó libre.");
    }
}
