<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel de inicio: resumen distinto según el rol del usuario.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $usuario = $request->user();
        $hoy = today()->toDateString();

        $citasHoy = Cita::with('paciente', 'medico', 'especialidad')
            ->whereDate('fecha', $hoy)
            ->when($usuario->tieneRol('Medico'), fn ($q) => $q->where('medico_id', $usuario->medico?->id ?? 0))
            ->orderBy('hora_inicio')->get();

        return view('dashboard', [
            'citasHoy' => $citasHoy,
            'totales' => [
                'pacientes' => Paciente::where('activo', true)->count(),
                'medicos' => Medico::where('activo', true)->count(),
                'citasHoy' => $citasHoy->where('estado', '<>', Cita::CANCELADA)->count(),
                'proximas' => Cita::whereDate('fecha', '>', $hoy)->whereIn('estado', [Cita::PROGRAMADA, Cita::CONFIRMADA])
                    ->when($usuario->tieneRol('Medico'), fn ($q) => $q->where('medico_id', $usuario->medico?->id ?? 0))
                    ->count(),
            ],
        ]);
    }
}
