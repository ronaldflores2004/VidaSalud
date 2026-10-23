<?php

namespace App\Services\P4_GestionCitas\CU9_ConsultarDisponibilidad;

use App\Models\P2_GestionMedica\BloqueoAgenda;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P2_GestionMedica\Medico;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Capa de negocio – Calcula los horarios disponibles de un médico para una fecha (RF05, CU9).
 *
 * Reglas aplicadas:
 *  - RN04 el médico debe estar activo.
 *  - RN05 solo se ofrecen bloques dentro de su horario de atención.
 *  - RN06 no se ofrecen fechas ni horas pasadas.
 *  - RN07 / RN09 se descartan los bloques ocupados por citas no canceladas.
 *  - RN08 cada ficha dura 15 minutos.
 *  - RF05 se descartan los feriados y bloqueos de agenda (del médico o de toda la clínica).
 */
class DisponibilidadService
{
    /**
     * @param  int|null  $ignorarCitaId  cita a ignorar (útil al reprogramar)
     * @return Collection<int, array{inicio:string, fin:string, disponible:bool}>
     */
    public function bloques(Medico $medico, Carbon $fecha, ?int $ignorarCitaId = null): Collection
    {
        $ahora = Carbon::now();

        if (! $medico->activo || $fecha->copy()->endOfDay()->lt($ahora)) {
            return collect();
        }

        $horarios = $medico->horarios()
            ->where('activo', true)
            ->where('dia_semana', $fecha->dayOfWeekIso)
            ->get();

        // Citas que ocupan agenda ese día
        $ocupadas = Cita::where('medico_id', $medico->id)
            ->whereDate('fecha', $fecha->toDateString())
            ->whereIn('estado', Cita::ESTADOS_ACTIVOS)
            ->when($ignorarCitaId, fn ($q) => $q->where('id', '<>', $ignorarCitaId))
            ->get(['hora_inicio', 'hora_fin'])
            ->map(fn ($c) => [substr($c->hora_inicio, 0, 5), substr($c->hora_fin, 0, 5)]);

        // Feriados y bloqueos: todo el día (sin horas) o un rango de horas
        $bloqueos = BloqueoAgenda::queAfectan($medico->id, $fecha->toDateString())->get();
        if ($bloqueos->contains(fn ($b) => $b->hora_inicio === null)) {
            return collect();
        }
        $ocupadas = $ocupadas->merge($bloqueos->map(fn ($b) => [substr($b->hora_inicio, 0, 5), substr($b->hora_fin, 0, 5)]));

        $bloques = collect();

        $minutos = \App\Models\P2_GestionMedica\Horario::duracionFicha();   // 15 min (tabla configuracion)

        foreach ($horarios as $h) {
            $inicio = Carbon::parse($fecha->toDateString() . ' ' . $h->hora_inicio);
            $limite = Carbon::parse($fecha->toDateString() . ' ' . $h->hora_fin);

            while ($inicio->copy()->addMinutes($minutos)->lte($limite)) {
                $fin = $inicio->copy()->addMinutes($minutos);
                $ini = $inicio->format('H:i');
                $fn = $fin->format('H:i');

                $libre = $inicio->gt($ahora)
                    && $ocupadas->every(fn ($o) => ! ($ini < $o[1] && $o[0] < $fn));

                $bloques->push(['inicio' => $ini, 'fin' => $fn, 'disponible' => $libre]);
                $inicio = $fin;
            }
        }

        return $bloques->sortBy('inicio')->values();
    }

    /** Devuelve el bloque si está libre, o null si no lo está. */
    public function bloqueLibre(Medico $medico, Carbon $fecha, string $horaInicio, ?int $ignorarCitaId = null): ?array
    {
        return $this->bloques($medico, $fecha, $ignorarCitaId)
            ->first(fn ($b) => $b['inicio'] === substr($horaInicio, 0, 5) && $b['disponible']);
    }
}
