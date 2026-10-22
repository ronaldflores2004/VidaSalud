<?php

namespace App\Http\Controllers\GestionMedica;

use App\Http\Controllers\Controller;
use App\Models\Horario;
use App\Models\Medico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU6 Gestionar horarios de atención de un médico (RF05).
 * Reglas: hora final posterior a la inicial y sin bloques cruzados el mismo día.
 */
class HorarioController extends Controller
{
    public function index(Medico $medico): View
    {
        return view('horarios.index', ['medico' => $medico->load('horarios', 'especialidades'), 'dias' => Horario::DIAS]);
    }

    public function store(Request $request, Medico $medico): RedirectResponse
    {
        $datos = $request->validate([
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ], [
            'hora_fin.after' => 'La hora final debe ser posterior a la hora inicial.',
        ], ['dia_semana' => 'día', 'hora_inicio' => 'hora inicial', 'hora_fin' => 'hora final']);
        $datos['duracion_cita'] = Horario::DURACION_FICHA; // 15 min por paciente

        $cruce = $medico->horarios()
            ->where('dia_semana', $datos['dia_semana'])
            ->where('activo', true)
            ->get()
            ->first(fn (Horario $h) => $h->seCruzaCon($datos['hora_inicio'], $datos['hora_fin']));

        if ($cruce) {
            return back()->withInput()->with('error', sprintf(
                'El horario se cruza con el bloque existente del %s de %s a %s.',
                $cruce->nombre_dia, substr($cruce->hora_inicio, 0, 5), substr($cruce->hora_fin, 0, 5)
            ));
        }

        $medico->horarios()->create($datos);

        return back()->with('ok', 'Horario de atención registrado.');
    }

    public function cambiarEstado(Medico $medico, Horario $horario): RedirectResponse
    {
        abort_unless($horario->medico_id === $medico->id, 404);
        $horario->update(['activo' => ! $horario->activo]);

        return back()->with('ok', 'Horario ' . ($horario->activo ? 'activado' : 'desactivado') . '.');
    }

    public function destroy(Medico $medico, Horario $horario): RedirectResponse
    {
        abort_unless($horario->medico_id === $medico->id, 404);
        // Las citas guardan su propia hora, por lo que quitar un horario no altera el historial
        $horario->delete();

        return back()->with('ok', 'Horario eliminado.');
    }
}
