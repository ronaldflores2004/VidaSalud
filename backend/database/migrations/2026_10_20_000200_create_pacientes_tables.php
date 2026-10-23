<?php

/**
 * P3 Gestión de Pacientes – pacientes, contactos_emergencia.
 *
 * Normalización:
 *  - 1FN: el contacto de emergencia era un grupo repetitivo dentro de pacientes
 *    (nombre, parentesco, teléfono) → tabla contactos_emergencia (un paciente puede tener varios).
 *  - Las alergias son información clínica y se guardan solo en historias_clinicas (P5).
 * El paciente nunca se elimina: se desactiva para conservar su historial (RN11).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 12)->unique();         // PAC-0001
            $table->string('ci', 20)->nullable()->unique(); // RF02: CI único cuando se proporciona
            $table->string('nombres', 80);
            $table->string('apellidos', 80);
            $table->date('fecha_nacimiento');
            $table->char('sexo', 1);                         // M / F / O
            $table->string('telefono', 20);
            $table->string('email', 120)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('tipo_sangre', 3)->nullable();
            $table->boolean('acepta_notificaciones')->default(true);
            $table->boolean('activo')->default(true);
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['apellidos', 'nombres']);
        });

        Schema::create('contactos_emergencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->string('parentesco', 40);
            $table->string('telefono', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contactos_emergencia');
        Schema::dropIfExists('pacientes');
    }
};
