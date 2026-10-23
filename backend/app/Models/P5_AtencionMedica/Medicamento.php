<?php

namespace App\Models\P5_AtencionMedica;

use Illuminate\Database\Eloquent\Model;

/** Catálogo de medicamentos: el tratamiento lo referencia en lugar de repetir el nombre. */
class Medicamento extends Model
{
    protected $table = 'medicamentos';
    protected $fillable = ['nombre', 'presentacion', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
