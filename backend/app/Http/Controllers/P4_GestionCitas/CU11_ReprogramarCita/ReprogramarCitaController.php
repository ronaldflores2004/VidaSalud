<?php

namespace App\Http\Controllers\P4_GestionCitas\CU11_ReprogramarCita;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Models\P4_GestionCitas\Cita;
use App\Services\P4_GestionCitas\CU11_ReprogramarCita\ReprogramarCitaService;
use App\Support\PoliticaCitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU11 Reprogramar cita: la recepcionista (o el propio paciente, solo sus citas) cambia la fecha
 * y la hora de una cita vigente con el mismo médico. Incluye a CU9 para mostrar las fichas libres.
 * RN18: hasta 2 horas antes y como máximo 2 reprogramaciones por cita (valores configurables).
 */
class ReprogramarCitaController extends Controller
{
    public function __construct(private ReprogramarCitaService $servicio)
    {
    }

    private function autorizar(Request $request, Cita $cita): void
    {
        $u = $request->user();
        abort_if($u->tieneRol('Paciente') && $cita->paciente_id !== $u->paciente?->id, 403, 'Solo puede reprogramar sus propias citas.');
    }

    public function edit(Request $request, Cita $cita): View|RedirectResponse
    {
        $this->autorizar($request, $cita);
        if ($motivo = PoliticaCitas::motivoNoReprogramable($cita)) {
            return redirect()->route('citas.show', $cita)->with('error', $motivo);
        }

        return view('P4_GestionCitas.CU11_ReprogramarCita.form', [
            'cita' => $cita->load('paciente', 'medico', 'especialidad'),
            'restantes' => PoliticaCitas::maxReprogramaciones() - PoliticaCitas::reprogramaciones($cita),
            'horasMin' => PoliticaCitas::horasReprogramar(),
        ]);
    }

    public function update(Request $request, Cita $cita): RedirectResponse
    {
        $this->autorizar($request, $cita);
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
