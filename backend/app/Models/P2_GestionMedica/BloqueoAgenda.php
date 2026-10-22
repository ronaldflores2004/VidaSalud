<?php

namespace App\Models\P2_GestionMedica;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feriado o bloqueo de agenda (RF05). medico_id NULL = toda la clínica;
 * hora_inicio/hora_fin NULL = todo el día.
 */
class BloqueoAgenda extends Model
{
    protected $table = 'bloqueos_agenda';

    protected $fillable = ['medico_id', 'fecha', 'hora_inicio', 'hora_fin', 'tipo', 'motivo', 'registrado_por'];

    protected $casts = ['fecha' => 'date'];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    /** Bloqueos que afectan a un médico en una fecha (los suyos y los de toda la clínica). */
    public function scopeQueAfectan(Builder $q, int $medicoId, string $fecha): Builder
    {
        return $q->whereDate('fecha', $fecha)
            ->where(fn ($w) => $w->whereNull('medico_id')->orWhere('medico_id', $medicoId));
    }
}
