<?php

namespace App\Models\P4_GestionCitas;

use App\Models\P1_SeguridadAcceso\User;
use App\Models\P2_GestionMedica\Especialidad;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P3_GestionPacientes\Paciente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entidad Cita médica. Nunca se elimina físicamente (RF09); cambia de estado.
 */
class Cita extends Model
{
    public const PROGRAMADA = 'PROGRAMADA';
    public const CONFIRMADA = 'CONFIRMADA';
    public const ATENDIDA = 'ATENDIDA';
    public const CANCELADA = 'CANCELADA';
    public const INASISTENCIA = 'INASISTENCIA';

    /** Estados que ocupan el horario del médico (las canceladas lo liberan, RN09). */
    public const ESTADOS_ACTIVOS = [self::PROGRAMADA, self::CONFIRMADA, self::ATENDIDA, self::INASISTENCIA];

    protected $fillable = [
        'codigo', 'paciente_id', 'medico_id', 'especialidad_id', 'fecha', 'hora_inicio', 'hora_fin', 'modalidad',
        'motivo', 'estado', 'registrado_por',
    ];

    protected $casts = ['fecha' => 'date'];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }

    public function estadoCita(): BelongsTo
    {
        return $this->belongsTo(EstadoCita::class, 'estado', 'codigo');
    }

    public function historial(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HistorialCita::class)->orderBy('created_at');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function getHorarioAttribute(): string
    {
        return substr($this->hora_inicio, 0, 5) . ' – ' . substr($this->hora_fin, 0, 5);
    }

    public static function siguienteCodigo(): string
    {
        return sprintf('CM-%04d', (int) static::max('id') + 1);
    }
}
