<?php

namespace App\Models\P5_AtencionMedica;

use App\Models\P3_GestionPacientes\Paciente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial clínico: uno por paciente. Se crea junto con el paciente; las consultas se registran en el Ciclo II.
 */
class HistoriaClinica extends Model
{
    protected $table = 'historias_clinicas';
    protected $fillable = ['numero', 'paciente_id', 'antecedentes_personales', 'antecedentes_familiares', 'alergias', 'enfermedades_cronicas', 'medicacion_habitual'];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public static function siguienteNumero(): string
    {
        return sprintf('HC-%04d', (int) static::max('id') + 1);
    }
}
