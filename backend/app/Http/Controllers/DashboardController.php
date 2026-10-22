<?php

namespace App\Http\Controllers;

use App\Models\P4_GestionCitas\Cita;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P3_GestionPacientes\Paciente;
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
            ->when($usuario->tieneRol('Paciente'), fn ($q) => $q->where('paciente_id', $usuario->paciente?->id ?? 0))
            ->orderBy('hora_inicio')->get();

        // Panel del paciente: sus próximas fichas
        if ($usuario->tieneRol('Paciente')) {
            return view('compartido.dashboard-paciente', [
                'paciente' => $usuario->paciente,
                'proximas' => Cita::with('medico', 'especialidad')
                    ->where('paciente_id', $usuario->paciente?->id ?? 0)
                    ->whereDate('fecha', '>=', $hoy)
                    ->whereIn('estado', [Cita::PROGRAMADA, Cita::CONFIRMADA])
                    ->orderBy('fecha')->orderBy('hora_inicio')->get(),
            ]);
        }

        return view('compartido.dashboard', [
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
