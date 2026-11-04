<?php

namespace App\Services\P6_Reportes\CU20_GenerarReportes;

use App\Models\P2_GestionMedica\Especialidad;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P3_GestionPacientes\Paciente;
use App\Models\P4_GestionCitas\Cita;
use App\Support\PdfSimple;
use App\Support\XlsxSimple;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Capa de negocio – CU20 Generar reportes (RF13).
 * Arma tres reportes con la misma estructura (título, columnas, filas y resumen)
 * y los exporta a PDF o a Excel (.xlsx):
 *   - CITAS: citas por fecha, médico, especialidad y estado.
 *   - PACIENTES: pacientes registrados en el período.
 *   - ATENCIONES: atendidas, canceladas e inasistencias por médico.
 */
class ReporteService
{
    public const TIPOS = [
        'citas' => 'Citas por período',
        'pacientes' => 'Pacientes registrados',
        'atenciones' => 'Atenciones por médico',
    ];

    /** @return array{titulo:string, filtros:string, columnas:array, filas:array, resumen:array} */
    public function generar(array $f): array
    {
        $desde = Carbon::parse($f['desde']);
        $hasta = Carbon::parse($f['hasta']);
        $filtros = 'Del ' . $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y');
        if (! empty($f['medico_id'])) {
            $filtros .= ' · Médico: ' . Medico::find($f['medico_id'])?->nombre_completo;
        }
        if (! empty($f['especialidad_id'])) {
            $filtros .= ' · Especialidad: ' . Especialidad::find($f['especialidad_id'])?->nombre;
        }
        if (! empty($f['estado'])) {
            $filtros .= ' · Estado: ' . ucfirst(strtolower($f['estado']));
        }

        return match ($f['tipo']) {
            'pacientes' => $this->pacientes($desde, $hasta, $filtros),
            'atenciones' => $this->atenciones($desde, $hasta, $f, $filtros),
            default => $this->citas($desde, $hasta, $f, $filtros),
        };
    }

    private function filtrarCitas($q, Carbon $desde, Carbon $hasta, array $f)
    {
        return $q->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->when($f['medico_id'] ?? null, fn ($w, $m) => $w->where('medico_id', $m))
            ->when($f['especialidad_id'] ?? null, fn ($w, $e) => $w->where('especialidad_id', $e));
    }

    private function citas(Carbon $desde, Carbon $hasta, array $f, string $filtros): array
    {
        $citas = $this->filtrarCitas(Cita::with('paciente', 'medico', 'especialidad'), $desde, $hasta, $f)
            ->when($f['estado'] ?? null, fn ($w, $e) => $w->where('estado', $e))
            ->orderBy('fecha')->orderBy('hora_inicio')->get();

        $porEstado = $citas->countBy('estado');
        $resumen = [['Total de citas', $citas->count()]];
        foreach ([Cita::PROGRAMADA, Cita::CONFIRMADA, Cita::ATENDIDA, Cita::CANCELADA, Cita::INASISTENCIA] as $e) {
            $resumen[] = [ucfirst(strtolower($e)) . 's', $porEstado[$e] ?? 0];
        }

        return [
            'titulo' => 'Reporte de citas por período',
            'filtros' => $filtros,
            'columnas' => [['Código', 8], ['Fecha', 9], ['Hora', 7], ['Paciente', 22], ['Médico', 22], ['Especialidad', 17], ['Estado', 11]],
            'filas' => $citas->map(fn ($c) => [$c->codigo, $c->fecha->format('d/m/Y'), $c->horario, $c->paciente->nombre_completo,
                $c->medico->nombre_completo, $c->especialidad->nombre, ucfirst(strtolower($c->estado))])->all(),
            'resumen' => $resumen,
        ];
    }

    private function pacientes(Carbon $desde, Carbon $hasta, string $filtros): array
    {
        $pacientes = Paciente::withCount('citas')
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde->toDateString(), $hasta->toDateString()])
            ->orderBy('apellidos')->get();

        return [
            'titulo' => 'Reporte de pacientes registrados',
            'filtros' => $filtros,
            'columnas' => [['Código', 9], ['CI', 9], ['Paciente', 26], ['Edad', 6], ['Sexo', 6], ['Teléfono', 10], ['Registro', 10], ['Citas', 6], ['Cuenta web', 9], ['Estado', 9]],
            'filas' => $pacientes->map(fn ($p) => [$p->codigo, $p->ci ?? '—', $p->nombre_completo, $p->edad, $p->sexo, $p->telefono,
                $p->created_at->format('d/m/Y'), $p->citas_count, $p->user_id ? 'Sí' : 'No', $p->activo ? 'Activo' : 'Inactivo'])->all(),
            'resumen' => [
                ['Pacientes registrados', $pacientes->count()],
                ['Con cuenta web', $pacientes->whereNotNull('user_id')->count()],
                ['Activos', $pacientes->where('activo', true)->count()],
            ],
        ];
    }

    private function atenciones(Carbon $desde, Carbon $hasta, array $f, string $filtros): array
    {
        $filas = $this->filtrarCitas(Cita::query(), $desde, $hasta, $f)
            ->select('medico_id',
                DB::raw("COUNT(*) AS total"),
                DB::raw("COUNT(*) FILTER (WHERE estado = 'ATENDIDA') AS atendidas"),
                DB::raw("COUNT(*) FILTER (WHERE estado = 'CANCELADA') AS canceladas"),
                DB::raw("COUNT(*) FILTER (WHERE estado = 'INASISTENCIA') AS inasistencias"),
                DB::raw("COUNT(*) FILTER (WHERE estado IN ('PROGRAMADA','CONFIRMADA')) AS pendientes"))
            ->groupBy('medico_id')->get()
            ->map(function ($r) {
                $m = Medico::with('especialidades')->find($r->medico_id);
                $base = $r->atendidas + $r->inasistencias;

                return [$m->nombre_completo, $m->especialidades->pluck('nombre')->join(', '), (int) $r->total, (int) $r->atendidas,
                    (int) $r->canceladas, (int) $r->inasistencias, (int) $r->pendientes, $base ? round($r->atendidas * 100 / $base, 1) . ' %' : '—'];
            })->sortBy(0)->values();

        return [
            'titulo' => 'Reporte de atenciones por médico',
            'filtros' => $filtros,
            'columnas' => [['Médico', 22], ['Especialidad', 22], ['Total', 7], ['Atendidas', 9], ['Canceladas', 9], ['Inasistencias', 10], ['Pendientes', 9], ['% asistencia', 10]],
            'filas' => $filas->all(),
            'resumen' => [
                ['Total de citas', $filas->sum(2)],
                ['Atendidas', $filas->sum(3)],
                ['Canceladas', $filas->sum(4)],
                ['Inasistencias', $filas->sum(5)],
            ],
        ];
    }

    // ------------------------------------------------------------------ exportación

    public function pdf(array $r, string $usuario): string
    {
        $pdf = new PdfSimple(horizontal: true);
        $m = 36;
        $anchoUtil = $pdf->ancho - 2 * $m;
        $total = array_sum(array_column($r['columnas'], 1));
        $anchos = array_map(fn ($c) => $c[1] * $anchoUtil / $total, $r['columnas']);
        $azul = [0.122, 0.306, 0.475];
        $pagina = 0;

        $cabecera = function () use ($pdf, $m, $anchoUtil, $r, $azul, &$pagina, $usuario) {
            $pdf->nuevaPagina();
            $pagina++;
            $pdf->rect($m, 28, $anchoUtil, 42, $azul, null);
            $pdf->texto($m + 12, 46, 'CLÍNICA VIDA SALUD', 9, true, [1, 1, 1]);
            $pdf->texto($m + 12, 62, $r['titulo'], 14, true, [1, 1, 1]);
            $pdf->texto($m, 62, 'Generado el ' . now()->format('d/m/Y H:i') . ' por ' . $usuario, 8, false, [1, 1, 1], 'R', $anchoUtil - 12);
            $pdf->texto($m, 86, $r['filtros'], 9, false, [0.3, 0.3, 0.3]);
            $pdf->texto($m, $pdf->alto - 20, 'Sistema Web de Gestión de Citas Médicas – Reporte CU20', 7.5, false, [0.45, 0.45, 0.45]);
            $pdf->texto($m, $pdf->alto - 20, 'Página ' . $pagina, 7.5, false, [0.45, 0.45, 0.45], 'R', $anchoUtil);
        };
        $encabezadoTabla = function (float $y) use ($pdf, $m, $anchos, $r, $azul) {
            $x = $m;
            foreach ($r['columnas'] as $i => [$titulo]) {
                $pdf->rect($x, $y, $anchos[$i], 18, $azul, null);
                $pdf->texto($x + 4, $y + 12.5, $pdf->ajustar($titulo, $anchos[$i] - 8, 8.5, true), 8.5, true, [1, 1, 1]);
                $x += $anchos[$i];
            }

            return $y + 18;
        };

        $cabecera();
        // Resumen (tarjetas)
        $y = 98;
        $wk = min(120, $anchoUtil / max(1, count($r['resumen'])) - 6);
        foreach ($r['resumen'] as $i => [$etiqueta, $valor]) {
            $x = $m + $i * ($wk + 6);
            $pdf->rect($x, $y, $wk, 34, [0.95, 0.97, 0.99], [0.8, 0.85, 0.9]);
            $pdf->texto($x + 8, $y + 13, $pdf->ajustar($etiqueta, $wk - 12, 7.5), 7.5, false, [0.35, 0.35, 0.35]);
            $pdf->texto($x + 8, $y + 28, (string) $valor, 12, true, $azul);
        }
        $y = $encabezadoTabla($y + 48);

        if (! $r['filas']) {
            $pdf->texto($m + 4, $y + 16, 'No hay datos para los filtros seleccionados.', 9, false, [0.4, 0.4, 0.4]);
        }
        foreach ($r['filas'] as $n => $fila) {
            if ($y + 16 > $pdf->alto - 36) {
                $cabecera();
                $y = $encabezadoTabla(98);
            }
            if ($n % 2) {
                $pdf->rect($m, $y, $anchoUtil, 16, [0.965, 0.975, 0.99], null);
            }
            $x = $m;
            foreach (array_values($fila) as $i => $valor) {
                $pdf->texto($x + 4, $y + 11, $pdf->ajustar((string) $valor, $anchos[$i] - 8, 8), 8);
                $x += $anchos[$i];
            }
            $pdf->linea($m, $y + 16, $m + $anchoUtil, $y + 16, [0.85, 0.85, 0.85]);
            $y += 16;
        }

        return $pdf->salida();
    }

    public function excel(array $r, string $usuario): string
    {
        $titulos = array_column($r['columnas'], 0);
        $filas = array_merge([$titulos], array_map('array_values', $r['filas']));
        $resumen = array_merge([['Clínica Vida Salud – ' . $r['titulo'], ''], [$r['filtros'], ''], ['Generado por ' . $usuario . ' el ' . now()->format('d/m/Y H:i'), ''], ['', ''], ['Indicador', 'Valor']], $r['resumen']);

        return (new XlsxSimple())
            ->hoja('Datos', $filas, array_map(fn ($c) => max(10, $c[1] * 1.3), $r['columnas']))
            ->hoja('Resumen', $resumen, [48, 14], 1)
            ->salida();
    }
}
