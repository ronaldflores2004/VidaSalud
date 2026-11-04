<?php

namespace App\Http\Controllers\P5_AtencionMedica\CU18_GestionarEstudiosMedicos;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Models\P5_AtencionMedica\Consulta;
use App\Models\P5_AtencionMedica\Estudio;
use App\Services\P5_AtencionMedica\CU18_GestionarEstudiosMedicos\EstudioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CU18 Gestionar estudios médicos: el médico pide uno o varios estudios en la consulta,
 * ve los pendientes y registra el resultado (texto y archivo PDF o imagen).
 */
class EstudioController extends Controller
{
    public function __construct(private EstudioService $servicio)
    {
    }

    private function esDelMedico(Request $request, Estudio $estudio): bool
    {
        return $estudio->consulta->cita->medico_id === $request->user()->medico?->id;
    }

    /** Estudios que pidió el médico, primero los que no tienen resultado. */
    public function index(Request $request): View
    {
        $medicoId = $request->user()->medico?->id ?? 0;
        $estudios = Estudio::with('catalogo', 'consulta.cita.paciente')
            ->whereHas('consulta.cita', fn ($q) => $q->where('medico_id', $medicoId))
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->orderByRaw("CASE WHEN estado = 'SOLICITADO' THEN 0 ELSE 1 END")->latest('fecha_solicitud')
            ->paginate(15)->withQueryString();

        return view('P5_AtencionMedica.CU18_GestionarEstudiosMedicos.index', compact('estudios'));
    }

    public function store(Request $request, Consulta $consulta): RedirectResponse
    {
        abort_if($consulta->cita->medico_id !== $request->user()->medico?->id, 403);
        $datos = $request->validate([
            'catalogo_estudio_id' => ['required', 'integer', 'exists:catalogo_estudios,id'],
            'indicaciones' => ['nullable', 'string', 'max:255'],
        ], [], ['catalogo_estudio_id' => 'estudio']);

        try {
            $this->servicio->solicitar($consulta, (int) $datos['catalogo_estudio_id'], $datos['indicaciones'] ?? null, $request->user());
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', 'Estudio solicitado.');
    }

    public function edit(Request $request, Estudio $estudio): View
    {
        abort_unless($this->esDelMedico($request, $estudio), 403);

        return view('P5_AtencionMedica.CU18_GestionarEstudiosMedicos.resultado', ['estudio' => $estudio->load('catalogo', 'consulta.cita.paciente')]);
    }

    public function update(Request $request, Estudio $estudio): RedirectResponse
    {
        abort_unless($this->esDelMedico($request, $estudio), 403);
        $datos = $request->validate([
            'estado' => ['required', 'in:REALIZADO,ENTREGADO'],
            'fecha_resultado' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'resultado' => ['required', 'string', 'min:5', 'max:4000'],
            'archivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
        ], [
            'archivo.mimes' => 'El archivo debe ser PDF, JPG o PNG.',
            'archivo.max' => 'El archivo no puede pesar más de 4 MB.',
            'fecha_resultado.before_or_equal' => 'La fecha del resultado no puede ser futura.',
        ], ['fecha_resultado' => 'fecha del resultado']);

        try {
            $this->servicio->registrarResultado($estudio, collect($datos)->except('archivo')->all(), $request->file('archivo'), $request->user());
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('consultas.show', $estudio->consulta_id)->with('ok', 'Resultado registrado. El paciente ya puede verlo en su historial.');
    }

    /** Descarga del archivo del resultado: el médico que lo pidió o el propio paciente. */
    public function archivo(Request $request, Estudio $estudio): StreamedResponse
    {
        $u = $request->user();
        $esPaciente = $u->tieneRol('Paciente') && $estudio->consulta->cita->paciente_id === $u->paciente?->id;
        abort_unless($this->esDelMedico($request, $estudio) || $esPaciente, 403);
        abort_unless($estudio->archivo_resultado && Storage::disk('local')->exists($estudio->archivo_resultado), 404);

        $nombre = str_replace(' ', '_', $estudio->catalogo->nombre) . '.' . pathinfo($estudio->archivo_resultado, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($estudio->archivo_resultado, $nombre);
    }
}
