<?php

namespace App\Models\P4_GestionCitas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recordatorios y avisos al paciente. Se refieren a una cita o a un estudio;
 * el paciente se obtiene de ellos (no se repite paciente_id, 3FN).
 */
class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = ['cita_id', 'estudio_id', 'tipo', 'canal', 'mensaje', 'programada_para', 'enviada_en', 'leida_en'];

    protected $casts = ['programada_para' => 'datetime', 'enviada_en' => 'datetime', 'leida_en' => 'datetime'];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public const TIPOS = ['RECORDATORIO' => 'Recordatorio', 'CANCELACION' => 'Cancelación', 'REPROGRAMACION' => 'Reprogramación', 'RESULTADO' => 'Resultado'];

    public function getTipoTextoAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function estudio(): BelongsTo
    {
        return $this->belongsTo(\App\Models\P5_AtencionMedica\Estudio::class);
    }
}
