<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entidad Rol: define qué opciones del sistema ve cada usuario.
 */
class Rol extends Model
{
    public const ADMINISTRADOR = 'Administrador';
    public const RECEPCIONISTA = 'Recepcionista';
    public const MEDICO = 'Medico';
    public const PACIENTE = 'Paciente';

    protected $table = 'roles';

    protected $fillable = ['nombre', 'descripcion'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}
