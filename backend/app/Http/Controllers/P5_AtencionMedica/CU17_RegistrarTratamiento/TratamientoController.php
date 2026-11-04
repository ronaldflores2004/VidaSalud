<?php

namespace App\Http\Controllers\P5_AtencionMedica\CU17_RegistrarTratamiento;

use App\Http\Controllers\Controller;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P5_AtencionMedica\Consulta;
use App\Models\P5_AtencionMedica\Tratamiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * CU17 Registrar tratamiento: el médico agrega o quita medicamentos de una consulta que él atendió.
 * (También se pueden indicar al registrar la consulta, <<extend>> de CU15.)
 */
class TratamientoController extends Controller
{
    private function autorizar(Request $request, Consulta $consulta): void
    {
        abort_if($consulta->cita->medico_id !== $request->user()->medico?->id, 403, 'Solo el médico que atendió puede indicar tratamientos.');
    }

    public function store(Request $request, Consulta $consulta): RedirectResponse
    {
        $this->autorizar($request, $consulta);
        $datos = $request->validate([
            'medicamento_id' => ['required', 'integer', 'exists:medicamentos,id'],
            'dosis' => ['required', 'string', 'max:60'],
            'frecuencia' => ['required', 'string', 'max:60'],
            'duracion' => ['required', 'string', 'max:60'],
            'indicaciones' => ['nullable', 'string', 'max:255'],
        ], [], ['medicamento_id' => 'medicamento', 'duracion' => 'duración']);

        $t = $consulta->tratamientos()->create($datos);
        Bitacora::registrar('CREAR', 'P5_AtencionMedica', $t, 'Tratamiento: ' . $t->medicamento->nombre, $request->user()->id);

        return back()->with('ok', 'Medicamento agregado al tratamiento.');
    }

    public function destroy(Request $request, Tratamiento $tratamiento): RedirectResponse
    {
        $this->autorizar($request, $tratamiento->consulta);
        Bitacora::registrar('ELIMINAR', 'P5_AtencionMedica', $tratamiento, 'Tratamiento quitado: ' . $tratamiento->medicamento->nombre, $request->user()->id);
        $tratamiento->delete();

        return back()->with('ok', 'Medicamento quitado del tratamiento.');
    }
}
