<?php

namespace App\Support;

use App\Models\P2_GestionMedica\Configuracion;
use App\Models\P3_GestionPacientes\Paciente;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P4_GestionCitas\HistorialCita;

/**
 * Política de citas de la clínica (RN17 – RN20). Los valores se leen de la tabla configuracion,
 * así el administrador los cambia en «Política de citas» sin modificar el código.
 *
 *  RN17  Cancelar con reembolso hasta N horas antes (24). Después se puede cancelar, sin reembolso.
 *  RN18  Reprogramar hasta N horas antes (2) y como máximo M veces por cita (2).
 *  RN19  La inasistencia se marca después de N minutos de tolerancia (10); el pago no se reembolsa.
 *  RN20  Con N inasistencias (3) en los últimos D días (90) el paciente reserva solo en recepción.
 */
class PoliticaCitas
{
    public static function valor(string $clave, int $defecto): int
    {
        return (int) Configuracion::valor($clave, $defecto);
    }

    public static function horasReembolso(): int { return self::valor('CANCELACION_REEMBOLSO_HORAS', 24); }
    public static function horasReprogramar(): int { return self::valor('REPROGRAMACION_MIN_HORAS', 2); }
    public static function maxReprogramaciones(): int { return self::valor('REPROGRAMACION_MAXIMA', 2); }
    public static function toleranciaMin(): int { return self::valor('TOLERANCIA_LLEGADA_MIN', 10); }
    public static function limiteInasistencias(): int { return self::valor('INASISTENCIAS_LIMITE', 3); }
    public static function diasInasistencias(): int { return self::valor('INASISTENCIAS_DIAS', 90); }

    /** Horas que faltan para el inicio de la cita (negativo si ya pasó). */
    public static function horasHasta(Cita $cita): float
    {
        return now()->diffInSeconds($cita->inicio, false) / 3600;
    }

    /** RN17: ¿se devuelve el pago si se cancela ahora? */
    public static function cancelacionConReembolso(Cita $cita): bool
    {
        return self::horasHasta($cita) >= self::horasReembolso();
    }

    public static function reprogramaciones(Cita $cita): int
    {
        return HistorialCita::where('cita_id', $cita->id)->where('accion', 'REPROGRAMADA')->count();
    }

    /** RN18: devuelve el motivo por el que no se puede reprogramar, o null si se puede. */
    public static function motivoNoReprogramable(Cita $cita): ?string
    {
        if (! $cita->estaVigente()) {
            return 'Solo se pueden reprogramar citas programadas o confirmadas.';
        }
        if (self::horasHasta($cita) < self::horasReprogramar()) {
            return 'Las citas se reprograman hasta ' . self::horasReprogramar() . ' horas antes de su inicio (RN18). Puede cancelarla y reservar otra ficha.';
        }
        if (self::reprogramaciones($cita) >= self::maxReprogramaciones()) {
            return 'Esta cita ya fue reprogramada ' . self::maxReprogramaciones() . ' veces, el máximo permitido (RN18).';
        }

        return null;
    }

    /** RN19: la inasistencia se marca pasada la tolerancia. */
    public static function puedeMarcarInasistencia(Cita $cita): bool
    {
        return $cita->inicio->copy()->addMinutes(self::toleranciaMin())->lte(now());
    }

    public static function inasistenciasRecientes(Paciente $paciente): int
    {
        return Cita::where('paciente_id', $paciente->id)->where('estado', Cita::INASISTENCIA)
            ->whereDate('fecha', '>=', today()->subDays(self::diasInasistencias()))->count();
    }

    /** RN20: ¿el paciente puede reservar por la web? */
    public static function reservaWebBloqueada(Paciente $paciente): bool
    {
        return self::inasistenciasRecientes($paciente) >= self::limiteInasistencias();
    }
}
