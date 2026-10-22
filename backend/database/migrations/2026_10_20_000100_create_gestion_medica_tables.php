<?php

/**
 * Paquete: Gestión Médica
 * Tablas: especialidades, medicos, especialidad_medico (N:M), horarios.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            // Nombre en minúsculas y sin tildes: evita "Pediatría" y "pediatria" duplicados (RF04)
            $table->string('nombre_normalizado', 80)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 12)->unique();               // MED-0001
            $table->string('registro_profesional', 30)->unique();
            $table->string('nombres', 80);
            $table->string('apellidos', 80);
            $table->string('telefono', 20);
            $table->string('email', 120)->nullable();
            $table->boolean('activo')->default(true);
            // Cuenta de acceso del médico (opcional): permite que consulte su agenda
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('especialidad_medico', function (Blueprint $table) {
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->primary(['medico_id', 'especialidad_id']);
        });

        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana');              // 1 = lunes ... 7 = domingo (ISO-8601)
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unsignedSmallInteger('duracion_cita')->default(15); // 15 minutos de atención por paciente
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Reglas de RF05 protegidas también en la base de datos
        DB::statement('ALTER TABLE horarios ADD CONSTRAINT chk_horario_rango CHECK (hora_fin > hora_inicio)');
        DB::statement('ALTER TABLE horarios ADD CONSTRAINT chk_horario_dia CHECK (dia_semana BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE horarios ADD CONSTRAINT chk_horario_duracion CHECK (duracion_cita = 15)');
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
        Schema::dropIfExists('especialidad_medico');
        Schema::dropIfExists('medicos');
        Schema::dropIfExists('especialidades');
    }
};
