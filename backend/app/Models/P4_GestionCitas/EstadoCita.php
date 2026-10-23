<?php

namespace App\Models\P4_GestionCitas;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de estados de la cita (PROGRAMADA, CONFIRMADA, ATENDIDA, CANCELADA, INASISTENCIA).
 */
class EstadoCita extends Model
{
    protected $table = 'estados_cita';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'ocupa_horario'];
    protected $casts = ['ocupa_horario' => 'boolean'];
}
