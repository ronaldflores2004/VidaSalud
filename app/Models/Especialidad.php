<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Entidad Especialidad médica (Medicina general, Pediatría, ...).
 */
class Especialidad extends Model
{
    protected $table = 'especialidades';

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function medicos(): BelongsToMany
    {
        return $this->belongsToMany(Medico::class, 'especialidad_medico');
    }

    /** "  Pediatría " -> "pediatria": se usa para impedir nombres repetidos. */
    public static function normalizar(string $nombre): string
    {
        return Str::of($nombre)->squish()->ascii()->lower()->value();
    }

    protected static function booted(): void
    {
        static::saving(function (Especialidad $e) {
            $e->nombre = Str::squish($e->nombre);
            $e->nombre_normalizado = self::normalizar($e->nombre);
        });
    }
}
