<?php

namespace App\Models\P7_PagosNotificaciones;

use App\Models\P1_SeguridadAcceso\User;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P5_AtencionMedica\Estudio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entidad Notificación (CU22). Se refiere a una cita o a un estudio (CHECK: exactamente uno).
 * El destinatario no se guarda (3FN): es el paciente de la cita/estudio o, si para_medico = true,
 * el médico de la cita. Se muestra en la bandeja del sistema y se envía como push al navegador.
 */
class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = ['cita_id', 'estudio_id', 'tipo', 'canal', 'mensaje', 'para_medico', 'programada_para', 'enviada_en', 'leida_en'];

    protected $casts = ['programada_para' => 'datetime', 'enviada_en' => 'datetime', 'leida_en' => 'datetime', 'para_medico' => 'boolean'];

    public const TIPOS = [
        'RECORDATORIO' => 'Recordatorio', 'CANCELACION' => 'Cancelación', 'REPROGRAMACION' => 'Reprogramación',
        'RESULTADO' => 'Resultado', 'PAGO' => 'Pago confirmado', 'NUEVA_CITA' => 'Nueva cita',
    ];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function estudio(): BelongsTo
    {
        return $this->belongsTo(Estudio::class);
    }

    public function getTipoTextoAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    /** Usuario que recibe el aviso: paciente (por la cita o el estudio) o el médico de la cita. */
    public function destinatario(): ?User
    {
        $cita = $this->cita ?? $this->estudio?->consulta?->cita;
        if (! $cita) {
            return null;
        }

        return $this->para_medico ? $cita->medico?->usuario : $cita->paciente?->usuario;
    }

    /** Pantalla que se abre al tocar la notificación. */
    public function getUrlAttribute(): string
    {
        if ($this->para_medico) {
            return $this->cita ? route('citas.show', $this->cita_id) : route('agenda');
        }

        return match ($this->tipo) {
            'RESULTADO' => route('mi-historial'),
            default => $this->cita_id ? route('citas.show', $this->cita_id) : route('notificaciones.index'),
        };
    }

    /** Notificaciones ya vigentes (no futuras) que le corresponden al usuario. */
    public function scopeDelUsuario(Builder $q, User $usuario): Builder
    {
        $q->where('programada_para', '<=', now());
        if ($usuario->tieneRol('Medico')) {
            $medicoId = $usuario->medico?->id ?? 0;

            return $q->where('para_medico', true)->whereHas('cita', fn ($c) => $c->where('medico_id', $medicoId));
        }
        $pacienteId = $usuario->paciente?->id ?? 0;

        return $q->where('para_medico', false)->where(fn ($w) => $w
            ->whereHas('cita', fn ($c) => $c->where('paciente_id', $pacienteId))
            ->orWhereHas('estudio.consulta.cita', fn ($c) => $c->where('paciente_id', $pacienteId)));
    }
}
