<?php

namespace App\Http\Controllers\P4_GestionCitas;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P2_GestionMedica\Especialidad;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P3_GestionPacientes\Paciente;
use App\Services\P4_GestionCitas\CitaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU10 Reservar / programar cita (ficha de 15 min, módulo principal, RF06 y RF07).
 * La recepcionista elige al paciente; el paciente reserva desde la web para sí mismo.
 * También lista las citas con filtros; cada rol ve solo lo que le corresponde.
 */
class CitaController extends Controller
{
    public function __construct(private CitaService $citas)
    {
    }

    public function index(Request $request): View
    {
        $usuario = $request->user();

        $citas = Cita::with(['paciente', 'medico', 'especialidad'])
            // El médico solo ve su propia agenda y el paciente solo sus propias citas
            ->when($usuario->tieneRol('Medico'), fn ($q) => $q->where('medico_id', $usuario->medico?->id ?? 0))
            ->when($usuario->tieneRol('Paciente'), fn ($q) => $q->where('paciente_id', $usuario->paciente?->id ?? 0))
            ->when($request->fecha, fn ($q, $f) => $q->whereDate('fecha', $f))
            ->when($request->medico, fn ($q, $m) => $q->where('medico_id', $m))
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->orderByDesc('fecha')->orderBy('hora_inicio')
            ->paginate(12)->withQueryString();

        return view('P4_GestionCitas.citas.index', [
            'citas' => $citas,
            'medicos' => Medico::orderBy('apellidos')->get(),
            'estados' => [Cita::PROGRAMADA, Cita::CONFIRMADA, Cita::ATENDIDA, Cita::CANCELADA, Cita::INASISTENCIA],
        ]);
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();
        if ($usuario->tieneRol('Paciente')) {
            abort_unless($usuario->paciente?->activo, 403, 'Su registro de paciente no está activo.');
        }

        return view('P4_GestionCitas.citas.create', [
            'esPaciente' => $usuario->tieneRol('Paciente'),
            'especialidades' => Especialidad::where('activo', true)->whereHas('medicos', fn ($m) => $m->where('activo', true))->orderBy('nombre')->get(),
            // Si vuelve con error de validación se conserva el paciente elegido
            'pacienteSeleccionado' => $usuario->tieneRol('Paciente')
                ? $usuario->paciente
                : (($id = old('paciente_id', $request->paciente)) ? Paciente::where('activo', true)->find($id) : null),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // El paciente solo puede reservar para sí mismo: se ignora cualquier paciente_id enviado
        if ($request->user()->tieneRol('Paciente')) {
            $request->merge(['paciente_id' => $request->user()->paciente?->id]);
        }

        $datos = $request->validate([
            'paciente_id' => ['required', 'integer', 'exists:pacientes,id'],
            'especialidad_id' => ['required', 'integer', 'exists:especialidades,id'],
            'medico_id' => ['required', 'integer', 'exists:medicos,id'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'modalidad' => ['required', 'in:PRESENCIAL,VIRTUAL'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ], [
            'paciente_id.required' => 'Busque y seleccione un paciente.',
            'hora_inicio.required' => 'Seleccione un horario disponible.',
            'fecha.after_or_equal' => 'No se pueden registrar citas en fechas pasadas.',
        ], ['especialidad_id' => 'especialidad', 'medico_id' => 'médico']);

        try {
            $cita = $this->citas->programar($datos, $request->user());
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('citas.show', $cita)->with('ok', "Cita {$cita->codigo} programada correctamente.");
    }

    public function show(Request $request, Cita $cita): View
    {
        $usuario = $request->user();
        abort_if($usuario->tieneRol('Medico') && $cita->medico_id !== $usuario->medico?->id, 403);
        abort_if($usuario->tieneRol('Paciente') && $cita->paciente_id !== $usuario->paciente?->id, 403);

        return view('P4_GestionCitas.citas.show', ['cita' => $cita->load('paciente', 'medico', 'especialidad', 'registradoPor', 'historial.usuario')]);
    }
}
