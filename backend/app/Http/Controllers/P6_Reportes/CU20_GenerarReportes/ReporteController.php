<?php

namespace App\Http\Controllers\P6_Reportes\CU20_GenerarReportes;

use App\Http\Controllers\Controller;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P2_GestionMedica\Especialidad;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P4_GestionCitas\Cita;
use App\Services\P6_Reportes\CU20_GenerarReportes\ReporteService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * CU20 Generar reportes (RF13): el administrador elige el tipo de reporte y los filtros,
 * ve la vista previa y lo exporta a PDF o a Excel.
 */
class ReporteController extends Controller
{
    public function __construct(private ReporteService $servicio)
    {
    }

    private function filtros(Request $request): array
    {
        return $request->validate([
            'tipo' => ['nullable', 'in:' . implode(',', array_keys(ReporteService::TIPOS))],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'medico_id' => ['nullable', 'integer', 'exists:medicos,id'],
            'especialidad_id' => ['nullable', 'integer', 'exists:especialidades,id'],
            'estado' => ['nullable', 'in:PROGRAMADA,CONFIRMADA,ATENDIDA,CANCELADA,INASISTENCIA'],
        ], ['hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.'])
            + ['tipo' => 'citas', 'desde' => today()->subDays(30)->toDateString(), 'hasta' => today()->addDays(30)->toDateString()];
    }

    public function index(Request $request): View
    {
        $f = array_filter($this->filtros($request), fn ($v) => $v !== null) + ['tipo' => 'citas', 'desde' => today()->subDays(30)->toDateString(), 'hasta' => today()->addDays(30)->toDateString()];

        return view('P6_Reportes.CU20_GenerarReportes.index', [
            'f' => $f,
            'reporte' => $this->servicio->generar($f),
            'tipos' => ReporteService::TIPOS,
            'medicos' => Medico::orderBy('apellidos')->get(),
            'especialidades' => Especialidad::orderBy('nombre')->get(),
            'estados' => [Cita::PROGRAMADA, Cita::CONFIRMADA, Cita::ATENDIDA, Cita::CANCELADA, Cita::INASISTENCIA],
        ]);
    }

    public function exportar(Request $request, string $formato): Response
    {
        abort_unless(in_array($formato, ['pdf', 'excel'], true), 404);
        $f = array_filter($this->filtros($request), fn ($v) => $v !== null) + ['tipo' => 'citas', 'desde' => today()->subDays(30)->toDateString(), 'hasta' => today()->addDays(30)->toDateString()];
        $r = $this->servicio->generar($f);
        $nombre = 'reporte_' . $f['tipo'] . '_' . $f['desde'] . '_' . $f['hasta'];
        Bitacora::registrar('REPORTE', 'P6_Reportes', null, $r['titulo'] . ' (' . strtoupper($formato) . ')', $request->user()->id);

        if ($formato === 'pdf') {
            return response($this->servicio->pdf($r, $request->user()->name), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"$nombre.pdf\"",
            ]);
        }

        return response($this->servicio->excel($r, $request->user()->name), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"$nombre.xlsx\"",
        ]);
    }
}
