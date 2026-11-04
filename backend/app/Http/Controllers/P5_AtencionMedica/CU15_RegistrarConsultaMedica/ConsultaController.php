<?php

namespace App\Http\Controllers\P5_AtencionMedica\CU15_RegistrarConsultaMedica;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P5_AtencionMedica\CatalogoEstudio;
use App\Models\P5_AtencionMedica\Consulta;
use App\Models\P5_AtencionMedica\Medicamento;
use App\Services\P5_AtencionMedica\CU15_RegistrarConsultaMedica\ConsultaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU15 Registrar consulta médica. El médico atiende su cita del día: registra signos vitales,
 * examen físico y diagnóstico; opcionalmente indica medicamentos (CU17) y pide estudios (CU18).
 */
class ConsultaController extends Controller
{
    public function __construct(private ConsultaService $servicio)
    {
    }

    public function create(Request $request, Cita $cita): View|RedirectResponse
    {
        if ($error = $this->servicio->puedeAtender($cita, $request->user())) {
            abort_if($cita->medico_id !== $request->user()->medico?->id, 403, $error);

            return redirect()->route('agenda', ['fecha' => $cita->fecha->toDateString()])->with('error', $error);
        }
        $paciente = $cita->paciente;

        return view('P5_AtencionMedica.CU15_RegistrarConsultaMedica.form', [
            'cita' => $cita->load('especialidad'),
            'paciente' => $paciente,
            'historia' => $paciente->asegurarHistoria(),
            'anteriores' => $paciente->consultas()->with('cita.medico')->latest('fecha_hora')->limit(3)->get(),
            'medicamentos' => Medicamento::where('activo', true)->orderBy('nombre')->get(),
            'catalogo' => CatalogoEstudio::where('activo', true)->orderBy('categoria')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request, Cita $cita): RedirectResponse
    {
        abort_if($cita->medico_id !== $request->user()->medico?->id, 403);

        $datos = $request->validate([
            'motivo_consulta' => ['required', 'string', 'min:3', 'max:255'],
            'peso_kg' => ['nullable', 'numeric', 'between:0.5,400'],
            'talla_cm' => ['nullable', 'numeric', 'between:30,250'],
            'presion_arterial' => ['nullable', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'temperatura_c' => ['nullable', 'numeric', 'between:30,45'],
            'frecuencia_cardiaca' => ['nullable', 'integer', 'between:30,220'],
            'examen_fisico' => ['nullable', 'string', 'max:2000'],
            'diagnostico' => ['required', 'string', 'min:5', 'max:2000'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            // CU17: filas de medicamentos (las vacías se ignoran)
            'tratamientos' => ['nullable', 'array', 'max:10'],
            'tratamientos.*.medicamento_id' => ['nullable', 'integer', 'exists:medicamentos,id'],
            'tratamientos.*.dosis' => ['required_with:tratamientos.*.medicamento_id', 'nullable', 'string', 'max:60'],
            'tratamientos.*.frecuencia' => ['required_with:tratamientos.*.medicamento_id', 'nullable', 'string', 'max:60'],
            'tratamientos.*.duracion' => ['required_with:tratamientos.*.medicamento_id', 'nullable', 'string', 'max:60'],
            'tratamientos.*.indicaciones' => ['nullable', 'string', 'max:255'],
            // CU18: estudios marcados del catálogo
            'estudios' => ['nullable', 'array'],
            'estudios.*.sel' => ['nullable', 'in:1'],
            'estudios.*.indicaciones' => ['nullable', 'string', 'max:255'],
        ], [
            'presion_arterial.regex' => 'La presión arterial se escribe como 120/80.',
            'tratamientos.*.dosis.required_with' => 'Indique la dosis de cada medicamento.',
            'tratamientos.*.frecuencia.required_with' => 'Indique la frecuencia de cada medicamento.',
            'tratamientos.*.duracion.required_with' => 'Indique la duración de cada medicamento.',
        ], ['motivo_consulta' => 'motivo de consulta', 'diagnostico' => 'diagnóstico', 'frecuencia_cardiaca' => 'frecuencia cardiaca']);

        $tratamientos = collect($datos['tratamientos'] ?? [])->filter(fn ($t) => ! empty($t['medicamento_id']))
            ->map(fn ($t) => array_intersect_key($t, array_flip(['medicamento_id', 'dosis', 'frecuencia', 'duracion', 'indicaciones'])))->values()->all();
        $estudios = collect($datos['estudios'] ?? [])->filter(fn ($e) => ($e['sel'] ?? null) === '1')
            ->map(fn ($e, $id) => ['catalogo_estudio_id' => (int) $id, 'indicaciones' => $e['indicaciones'] ?? null])
            ->filter(fn ($e) => CatalogoEstudio::whereKey($e['catalogo_estudio_id'])->exists())->values()->all();
        unset($datos['tratamientos'], $datos['estudios']);

        try {
            $consulta = $this->servicio->registrar($cita, $datos, $tratamientos, $estudios, $request->user());
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('consultas.show', $consulta)->with('ok', 'Consulta registrada. La cita quedó como atendida.');
    }

    public function show(Request $request, Consulta $consulta): View
    {
        $consulta->load('cita.paciente', 'cita.medico', 'cita.especialidad', 'tratamientos.medicamento', 'estudios.catalogo');
        abort_if($consulta->cita->medico_id !== $request->user()->medico?->id, 403, 'Solo el médico que atendió puede ver esta consulta.');

        return view('P5_AtencionMedica.CU15_RegistrarConsultaMedica.show', [
            'consulta' => $consulta,
            'medicamentos' => Medicamento::where('activo', true)->orderBy('nombre')->get(),
            'catalogo' => CatalogoEstudio::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }
}
