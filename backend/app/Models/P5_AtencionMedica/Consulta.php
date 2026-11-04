<?php

namespace App\Models\P5_AtencionMedica;

use App\Models\P4_GestionCitas\Cita;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Consulta médica (CU15): una por cita atendida.
 * El médico y el paciente se obtienen de la cita (3FN), no se repiten aquí.
 */
class Consulta extends Model
{
    protected $table = 'consultas';

    protected $fillable = ['cita_id', 'fecha_hora', 'motivo_consulta', 'peso_kg', 'talla_cm', 'presion_arterial',
        'temperatura_c', 'frecuencia_cardiaca', 'examen_fisico', 'diagnostico', 'observaciones'];

    protected $casts = ['fecha_hora' => 'datetime'];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    /** CU17 Registrar tratamiento */
    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class)->orderBy('id');
    }

    /** CU18 Gestionar estudios médicos (uno o varios por consulta) */
    public function estudios(): HasMany
    {
        return $this->hasMany(Estudio::class)->orderBy('id');
    }

    /** Índice de masa corporal calculado (no se guarda: es un dato derivado). */
    public function getImcAttribute(): ?float
    {
        if (! $this->peso_kg || ! $this->talla_cm) {
            return null;
        }
        $m = $this->talla_cm / 100;

        return round($this->peso_kg / ($m * $m), 1);
    }
}
