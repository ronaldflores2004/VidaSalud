<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Especialidad;
use App\Models\Medico;
use Illuminate\View\View;

/**
 * Portal público de la clínica (no requiere iniciar sesión).
 * CU9 Consultar disponibilidad: los pacientes ven en la web los horarios libres de los médicos.
 */
class PortalController extends Controller
{
    public function inicio(): View
    {
        return view('portal.inicio', [
            'especialidades' => Especialidad::where('activo', true)
                ->withCount(['medicos' => fn ($m) => $m->where('activo', true)])
                ->orderBy('nombre')->get(),
            'medicos' => Medico::where('activo', true)->with(['especialidades', 'horarios' => fn ($h) => $h->where('activo', true)])
                ->orderBy('apellidos')->get(),
        ]);
    }

    public function horarios(): View
    {
        return view('portal.horarios', [
            'especialidades' => Especialidad::where('activo', true)
                ->whereHas('medicos', fn ($m) => $m->where('activo', true))->orderBy('nombre')->get(),
        ]);
    }
}
