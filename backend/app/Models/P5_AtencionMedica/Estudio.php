<?php

namespace App\Models\P5_AtencionMedica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Estudio que el médico manda al paciente (CU18) y su resultado.
 * Estados: SOLICITADO → REALIZADO → ENTREGADO.
 */
class Estudio extends Model
{
    public const SOLICITADO = 'SOLICITADO';
    public const REALIZADO = 'REALIZADO';
    public const ENTREGADO = 'ENTREGADO';

    protected $table = 'estudios';

    protected $fillable = ['consulta_id', 'catalogo_estudio_id', 'indicaciones', 'estado', 'fecha_solicitud',
        'fecha_resultado', 'resultado', 'archivo_resultado'];

    protected $casts = ['fecha_solicitud' => 'date', 'fecha_resultado' => 'date'];

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class);
    }

    public function catalogo(): BelongsTo
    {
        return $this->belongsTo(CatalogoEstudio::class, 'catalogo_estudio_id');
    }

    public function tieneResultado(): bool
    {
        return $this->estado !== self::SOLICITADO;
    }
}
