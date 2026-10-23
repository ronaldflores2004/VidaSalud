<?php

namespace App\Models\P5_AtencionMedica;

use Illuminate\Database\Eloquent\Model;

/** Catálogo de estudios (laboratorio, imagen u otro) que el médico puede solicitar. */
class CatalogoEstudio extends Model
{
    protected $table = 'catalogo_estudios';
    protected $fillable = ['nombre', 'categoria', 'descripcion', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
