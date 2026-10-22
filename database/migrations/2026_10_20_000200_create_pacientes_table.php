<?php

/**
 * Paquete: Gestión de Pacientes
 * El paciente nunca se elimina físicamente: se desactiva para conservar su historial (RN11).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 12)->unique();       // PAC-0001
            $table->string('ci', 20)->nullable()->unique(); // RF02: CI único cuando se proporciona
            $table->string('nombres', 80);
            $table->string('apellidos', 80);
            $table->date('fecha_nacimiento');
            $table->char('sexo', 1);                       // M / F / O
            $table->string('telefono', 20);
            $table->string('email', 120)->nullable();
            $table->string('direccion', 200)->nullable();
            // Información médica básica (perfil del paciente, sección 3.4)
            $table->string('tipo_sangre', 3)->nullable();
            $table->string('alergias', 255)->nullable();
            // Contacto de emergencia
            $table->string('contacto_emergencia_nombre', 120)->nullable();
            $table->string('contacto_emergencia_telefono', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['apellidos', 'nombres']);
        });

        // Búsqueda sin distinguir tildes ("lopez" encuentra "López")
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
