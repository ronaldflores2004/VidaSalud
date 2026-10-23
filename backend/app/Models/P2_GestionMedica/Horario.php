<?php

namespace App\Models\P2_GestionMedica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entidad Horario de atención: bloque semanal en el que un médico atiende.
 */
class Horario extends Model
{
    /** Minutos de atención por paciente (una ficha); se lee de la tabla configuracion. */
    public static function duracionFicha(): int
    {
        return (int) Configuracion::valor('DURACION_FICHA_MIN', 15);
    }

    public const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    protected $fillable = ['medico_id', 'dia_semana', 'hora_inicio', 'hora_fin', 'activo'];

    protected $casts = ['activo' => 'boolean', 'dia_semana' => 'integer'];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function getNombreDiaAttribute(): string
    {
        return self::DIAS[$this->dia_semana] ?? '-';
    }

    /** Verifica si este bloque se cruza con otro rango del mismo día. */
    public function seCruzaCon(string $inicio, string $fin): bool
    {
        return substr($this->hora_inicio, 0, 5) < $fin && $inicio < substr($this->hora_fin, 0, 5);
    }
}
