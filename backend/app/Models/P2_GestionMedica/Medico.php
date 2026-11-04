<?php

namespace App\Models\P2_GestionMedica;

use App\Models\P1_SeguridadAcceso\User;
use App\Models\P4_GestionCitas\Cita;
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

    /**
     * RN: el médico solo ve a sus propios pacientes, es decir, a los que tienen
     * o tuvieron una cita con él.
     */
    public function atiendeA(\App\Models\P3_GestionPacientes\Paciente $paciente): bool
    {
        return $this->citas()->where('paciente_id', $paciente->id)->where('estado', '<>', Cita::CANCELADA)->exists();
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
