<?php

/**
 * P4 Gestión de Citas – estados_cita, citas, historial_citas.
 *
 * Normalización:
 *  - Los estados de la cita son un catálogo (estados_cita) en lugar de texto repetido.
 *  - El motivo de cancelación ya no se repite en citas: queda en historial_citas,
 *    que guarda cada cambio de la cita (trazabilidad, RF08–RF12).
 *  - (medico_id, especialidad_id) es clave foránea compuesta a especialidad_medico:
 *    solo se puede reservar una especialidad que el médico realmente atiende.
 *
 * RN07: dos citas que ocupan el horario del mismo médico no pueden cruzarse (EXCLUDE).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_cita', function (Blueprint $table) {
            $table->string('codigo', 15)->primary();
            $table->string('nombre', 40);
            $table->string('descripcion', 150);
            $table->boolean('ocupa_horario');               // false = libera la ficha (CANCELADA)
        });
        DB::table('estados_cita')->insert([
            ['codigo' => 'PROGRAMADA', 'nombre' => 'Programada', 'descripcion' => 'La cita está reservada', 'ocupa_horario' => true],
            ['codigo' => 'CONFIRMADA', 'nombre' => 'Confirmada', 'descripcion' => 'Se confirmó la asistencia del paciente', 'ocupa_horario' => true],
            ['codigo' => 'ATENDIDA', 'nombre' => 'Atendida', 'descripcion' => 'La consulta se realizó', 'ocupa_horario' => true],
            ['codigo' => 'CANCELADA', 'nombre' => 'Cancelada', 'descripcion' => 'La cita fue cancelada y liberó la ficha', 'ocupa_horario' => false],
            ['codigo' => 'INASISTENCIA', 'nombre' => 'Inasistencia', 'descripcion' => 'El paciente no se presentó', 'ocupa_horario' => true],
        ]);

        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 12)->unique();       // CM-0001
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->unsignedBigInteger('medico_id');
            $table->unsignedBigInteger('especialidad_id');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('modalidad', 10)->default('PRESENCIAL');
            $table->string('motivo', 255)->nullable();
            $table->string('estado', 15)->default('PROGRAMADA');
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();

            $table->foreign(['medico_id', 'especialidad_id'])->references(['medico_id', 'especialidad_id'])->on('especialidad_medico')->restrictOnDelete();
            $table->foreign('estado')->references('codigo')->on('estados_cita');
            $table->index(['medico_id', 'fecha']);
            $table->index(['paciente_id', 'fecha']);
        });
        DB::statement("ALTER TABLE citas ADD CONSTRAINT chk_cita_rango CHECK (hora_fin > hora_inicio)");
        DB::statement("ALTER TABLE citas ADD CONSTRAINT chk_cita_modalidad CHECK (modalidad IN ('PRESENCIAL','VIRTUAL'))");
        DB::statement("ALTER TABLE citas ADD CONSTRAINT excl_cita_medico_sin_cruce
            EXCLUDE USING gist (medico_id WITH =, tsrange(fecha + hora_inicio, fecha + hora_fin) WITH &&)
            WHERE (estado <> 'CANCELADA')");

        Schema::create('historial_citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $table->string('accion', 15);                  // CREADA, REPROGRAMADA, CONFIRMADA, ATENDIDA, CANCELADA, INASISTENCIA
            $table->string('estado_anterior', 15)->nullable();
            $table->string('estado_nuevo', 15);
            $table->date('fecha_anterior')->nullable();
            $table->time('hora_anterior')->nullable();
            $table->date('fecha_nueva')->nullable();
            $table->time('hora_nueva')->nullable();
            $table->string('motivo', 255)->nullable();     // p. ej. motivo de cancelación o de reprogramación
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('estado_anterior')->references('codigo')->on('estados_cita');
            $table->foreign('estado_nuevo')->references('codigo')->on('estados_cita');
            $table->index(['cita_id', 'created_at']);
        });
        DB::statement("ALTER TABLE historial_citas ADD CONSTRAINT chk_historial_accion CHECK (accion IN
            ('CREADA','REPROGRAMADA','CONFIRMADA','ATENDIDA','CANCELADA','INASISTENCIA'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_citas');
        Schema::dropIfExists('citas');
        Schema::dropIfExists('estados_cita');
    }
};
