<?php

namespace App\Models\P4_GestionCitas;

use App\Models\P1_SeguridadAcceso\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trazabilidad de una cita: cada creación, reprogramación o cambio de estado (RF08–RF12).
 */
class HistorialCita extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'historial_citas';

    protected $fillable = ['cita_id', 'accion', 'estado_anterior', 'estado_nuevo', 'fecha_anterior', 'hora_anterior',
        'fecha_nueva', 'hora_nueva', 'motivo', 'user_id'];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
