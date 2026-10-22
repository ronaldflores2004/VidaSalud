<?php

/**
 * Paquete: Gestión de Citas
 *
 * La regla RN07 (un médico no puede tener citas superpuestas) se valida en el servidor
 * (CitaService) y además se garantiza en PostgreSQL con una restricción EXCLUDE:
 * dos citas activas del mismo médico no pueden tener rangos de tiempo que se crucen.
 * Las citas CANCELADAS no participan de la restricción, por eso liberan el horario (RN09).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 12)->unique();   // CM-0001
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('motivo', 255)->nullable();
            $table->string('estado', 15)->default('PROGRAMADA');
            $table->string('motivo_cancelacion', 255)->nullable();
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();

            $table->index(['medico_id', 'fecha']);
            $table->index(['paciente_id', 'fecha']);
        });

        DB::statement("ALTER TABLE citas ADD CONSTRAINT chk_cita_estado CHECK (estado IN
            ('PROGRAMADA','CONFIRMADA','ATENDIDA','CANCELADA','INASISTENCIA'))");
        DB::statement('ALTER TABLE citas ADD CONSTRAINT chk_cita_rango CHECK (hora_fin > hora_inicio)');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
            DB::statement("ALTER TABLE citas ADD CONSTRAINT excl_cita_medico_sin_cruce
                EXCLUDE USING gist (
                    medico_id WITH =,
                    tsrange(fecha + hora_inicio, fecha + hora_fin) WITH &&
                ) WHERE (estado <> 'CANCELADA')");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
