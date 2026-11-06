<?php

/**
 * Política de citas (RN17 – RN20): plazos y límites que la clínica puede cambiar sin tocar el código.
 * Se guardan en la tabla configuracion, igual que la duración de la ficha.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public const PARAMETROS = [
        ['CANCELACION_REEMBOLSO_HORAS', '24', 'RN17: horas de anticipación para cancelar con reembolso del pago'],
        ['REPROGRAMACION_MIN_HORAS', '2', 'RN18: horas de anticipación mínimas para reprogramar una cita'],
        ['REPROGRAMACION_MAXIMA', '2', 'RN18: cantidad máxima de reprogramaciones por cita'],
        ['TOLERANCIA_LLEGADA_MIN', '10', 'RN19: minutos de tolerancia antes de marcar la inasistencia'],
        ['INASISTENCIAS_LIMITE', '3', 'RN20: inasistencias que bloquean la reserva por la web'],
        ['INASISTENCIAS_DIAS', '90', 'RN20: días hacia atrás en que se cuentan las inasistencias'],
    ];

    public function up(): void
    {
        foreach (self::PARAMETROS as [$clave, $valor, $descripcion]) {
            DB::table('configuracion')->insertOrIgnore(['clave' => $clave, 'valor' => $valor, 'descripcion' => $descripcion, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('configuracion')->whereIn('clave', array_column(self::PARAMETROS, 0))->delete();
    }
};
