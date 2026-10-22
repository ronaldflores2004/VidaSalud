<?php

namespace App\Models\P1_SeguridadAcceso;

use App\Models\P2_GestionMedica\Medico;
use App\Models\P3_GestionPacientes\Paciente;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Entidad Usuario: cuenta de acceso al sistema.
 * La contraseña se guarda con hash bcrypt (cast "hashed" = password_hash de PHP).
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'username', 'email', 'password', 'rol_id', 'activo', 'ultimo_acceso'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
            'ultimo_acceso' => 'datetime',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function medico(): HasOne
    {
        return $this->hasOne(Medico::class, 'user_id');
    }

    public function paciente(): HasOne
    {
        return $this->hasOne(Paciente::class, 'user_id');
    }

    /** Verifica si el usuario tiene alguno de los roles indicados. */
    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol?->nombre, $roles, true);
    }

    public function estaActivo(): bool
    {
        return (bool) $this->activo;
    }
}
