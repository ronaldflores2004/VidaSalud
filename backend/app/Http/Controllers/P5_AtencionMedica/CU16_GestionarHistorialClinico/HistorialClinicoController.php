<?php

namespace App\Http\Controllers\P5_AtencionMedica\CU16_GestionarHistorialClinico;

use App\Http\Controllers\Controller;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P3_GestionPacientes\Paciente;
use App\Models\P4_GestionCitas\Cita;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU16 Gestionar historial clínico. El médico consulta el historial de sus pacientes
 * (antecedentes, alergias, consultas, tratamientos y estudios) y actualiza los antecedentes.
 * Regla: el médico solo accede a los pacientes que tienen o tuvieron cita con él.
 */
class HistorialClinicoController extends Controller
{
    private function autorizar(Request $request, Paciente $paciente): void
    {
        $medico = $request->user()->medico;
        abort_unless($medico && $medico->atiendeA($paciente), 403, 'Solo puede ver el historial de sus propios pacientes.');
    }

    /** Mis pacientes: los que tienen o tuvieron cita con el médico. */
    public function index(Request $request): View
    {
        $medicoId = $request->user()->medico?->id ?? 0;
        $pacientes = Paciente::with('historia')
            ->whereHas('citas', fn ($q) => $q->where('medico_id', $medicoId)->where('estado', '<>', Cita::CANCELADA))
            ->withCount(['citas as atenciones' => fn ($q) => $q->where('medico_id', $medicoId)->where('estado', Cita::ATENDIDA)])
            ->buscar($request->q)->orderBy('apellidos')->paginate(12)->withQueryString();

        return view('P5_AtencionMedica.CU16_GestionarHistorialClinico.index', compact('pacientes'));
    }

    public function show(Request $request, Paciente $paciente): View
    {
        $this->autorizar($request, $paciente);

        return view('P5_AtencionMedica.CU16_GestionarHistorialClinico.show', [
            'paciente' => $paciente->load('contactos'),
            'historia' => $paciente->asegurarHistoria(),
            'consultas' => $paciente->consultas()->with('cita.medico', 'cita.especialidad', 'tratamientos.medicamento', 'estudios.catalogo')
                ->latest('fecha_hora')->get(),
        ]);
    }

    public function update(Request $request, Paciente $paciente): RedirectResponse
    {
        $this->autorizar($request, $paciente);
        $datos = $request->validate([
            'antecedentes_personales' => ['nullable', 'string', 'max:2000'],
            'antecedentes_familiares' => ['nullable', 'string', 'max:2000'],
            'alergias' => ['nullable', 'string', 'max:255'],
            'enfermedades_cronicas' => ['nullable', 'string', 'max:255'],
            'medicacion_habitual' => ['nullable', 'string', 'max:255'],
        ]);
        $historia = $paciente->asegurarHistoria();
        $historia->update($datos);
        Bitacora::registrar('MODIFICAR', 'P5_AtencionMedica', $historia, "Antecedentes del historial {$historia->numero}", $request->user()->id);

        return back()->with('ok', 'Historial clínico actualizado.');
    }
}
