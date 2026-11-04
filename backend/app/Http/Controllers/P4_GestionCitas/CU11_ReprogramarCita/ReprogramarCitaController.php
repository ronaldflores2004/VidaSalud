<?php

namespace App\Http\Controllers\P4_GestionCitas\CU11_ReprogramarCita;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Models\P4_GestionCitas\Cita;
use App\Services\P4_GestionCitas\CU11_ReprogramarCita\ReprogramarCitaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU11 Reprogramar cita: la recepcionista cambia la fecha y la hora de una cita vigente
 * con el mismo médico. Incluye a CU9 para mostrar las fichas libres.
 */
class ReprogramarCitaController extends Controller
{
    public function __construct(private ReprogramarCitaService $servicio)
    {
    }

    public function edit(Cita $cita): View|RedirectResponse
    {
        if (! $cita->estaVigente() || $cita->inicio->lte(now())) {
            return redirect()->route('citas.show', $cita)->with('error', 'Esta cita ya no se puede reprogramar.');
        }

        return view('P4_GestionCitas.CU11_ReprogramarCita.form', ['cita' => $cita->load('paciente', 'medico', 'especialidad')]);
    }

    public function update(Request $request, Cita $cita): RedirectResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'motivo' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'hora_inicio.required' => 'Seleccione el nuevo horario.',
            'fecha.after_or_equal' => 'No se pueden elegir fechas pasadas.',
        ], ['motivo' => 'motivo de la reprogramación']);

        try {
            $this->servicio->reprogramar($cita, $datos['fecha'], $datos['hora_inicio'], $datos['motivo'], $request->user());
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('citas.show', $cita)->with('ok', "Cita {$cita->codigo} reprogramada correctamente.");
    }
}
