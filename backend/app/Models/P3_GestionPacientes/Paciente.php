<?php

namespace App\Models\P3_GestionPacientes;

use App\Models\P1_SeguridadAcceso\User;
use App\Models\P4_GestionCitas\Cita;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entidad Paciente. Se desactiva en lugar de eliminarse (RN11).
 */
class Paciente extends Model
{
    protected $fillable = [
        'codigo', 'ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'telefono', 'email', 'direccion',
        'tipo_sangre', 'alergias', 'contacto_emergencia_nombre', 'contacto_emergencia_telefono', 'activo', 'user_id',
    ];

    protected $casts = ['activo' => 'boolean', 'fecha_nacimiento' => 'date'];

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class)->orderByDesc('fecha')->orderByDesc('hora_inicio');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->nombres . ' ' . $this->apellidos;
    }

    public function getEdadAttribute(): int
    {
        return $this->fecha_nacimiento?->age ?? 0;
    }

    /** Búsqueda por CI, nombre o apellido (RF02). ILIKE = sin distinguir mayúsculas en PostgreSQL. */
    public function scopeBuscar(Builder $q, ?string $texto): Builder
    {
        if (! $texto) {
            return $q;
        }
        $like = '%' . trim($texto) . '%';

        // En PostgreSQL se usa unaccent() para que "lopez" encuentre "López"
        if ($q->getConnection()->getDriverName() === 'pgsql') {
            return $q->where(function ($w) use ($like) {
                foreach (['ci', 'nombres', 'apellidos', 'codigo'] as $col) {
                    $w->orWhereRaw("unaccent({$col}) ILIKE unaccent(?)", [$like]);
                }
            });
        }

        return $q->where(fn ($w) => $w->where('ci', 'like', $like)->orWhere('nombres', 'like', $like)
            ->orWhere('apellidos', 'like', $like)->orWhere('codigo', 'like', $like));
    }

    public static function siguienteCodigo(): string
    {
        return sprintf('PAC-%04d', (int) static::max('id') + 1);
    }
}
