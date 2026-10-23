<?php

namespace App\Models\P3_GestionPacientes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contacto de emergencia del paciente (1FN: tabla propia en lugar de columnas repetidas).
 */
class ContactoEmergencia extends Model
{
    protected $table = 'contactos_emergencia';
    protected $fillable = ['paciente_id', 'nombre', 'parentesco', 'telefono'];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
}
