<?php

namespace App\Models\P4_GestionCitas;

use App\Models\P3_GestionPacientes\Paciente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recordatorios y avisos al paciente (recordatorio de próxima consulta, cancelación, resultados…).
 */
class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = ['paciente_id', 'cita_id', 'tipo', 'canal', 'mensaje', 'programada_para', 'enviada_en', 'leida_en'];

    protected $casts = ['programada_para' => 'datetime', 'enviada_en' => 'datetime', 'leida_en' => 'datetime'];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }
}
