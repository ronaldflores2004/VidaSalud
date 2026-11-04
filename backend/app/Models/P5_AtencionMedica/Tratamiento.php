<?php

namespace App\Models\P5_AtencionMedica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tratamiento indicado en una consulta (CU17): medicamento del catálogo, dosis, frecuencia y duración.
 */
class Tratamiento extends Model
{
    protected $table = 'tratamientos';

    protected $fillable = ['consulta_id', 'medicamento_id', 'dosis', 'frecuencia', 'duracion', 'indicaciones'];

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class);
    }

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class);
    }
}
