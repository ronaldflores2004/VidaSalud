<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entidad Médico. Un médico inactivo no recibe nuevas citas (RN04),
 * pero conserva su historial.
 */
class Medico extends Model
{
    protected $fillable = ['codigo', 'registro_profesional', 'nombres', 'apellidos', 'telefono', 'email', 'activo', 'user_id'];

    protected $casts = ['activo' => 'boolean'];

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(Especialidad::class, 'especialidad_medico');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class)->orderBy('dia_semana')->orderBy('hora_inicio');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return 'Dr(a). ' . $this->nombres . ' ' . $this->apellidos;
    }

    /** Genera el siguiente código correlativo: MED-0001, MED-0002, ... */
    public static function siguienteCodigo(): string
    {
        return sprintf('MED-%04d', (int) static::max('id') + 1);
    }
}
