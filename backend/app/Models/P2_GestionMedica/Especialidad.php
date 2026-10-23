<?php

namespace App\Models\P2_GestionMedica;

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

    /** "  Pediatría " -> "pediatria" (equivale a la función f_normalizar de la base de datos). */
    public static function normalizar(string $nombre): string
    {
        return Str::of($nombre)->squish()->ascii()->lower()->value();
    }

    protected static function booted(): void
    {
        static::saving(fn (Especialidad $e) => $e->nombre = Str::squish($e->nombre));
    }

    /** ¿Existe otra especialidad con el mismo nombre normalizado? (usa f_normalizar de PostgreSQL) */
    public static function nombreRepetido(string $nombre, ?int $ignorarId = null): bool
    {
        return static::whereRaw('f_normalizar(nombre) = f_normalizar(?)', [$nombre])
            ->when($ignorarId, fn ($q) => $q->where('id', '<>', $ignorarId))->exists();
    }
}
