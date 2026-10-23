<?php

/**
 * P2 Gestión Médica – configuracion, especialidades, medicos, especialidad_medico, horarios, bloqueos_agenda.
 *
 * Normalización:
 *  - La duración de la ficha (15 min) es un parámetro único del sistema → tabla configuracion
 *    (antes se repetía en cada fila de horarios).
 *  - El nombre "normalizado" de la especialidad ya no se guarda: se calcula con f_normalizar()
 *    y un índice único impide "Pediatría" y "pediatria" duplicados (RF04).
 *  - Médico–Especialidad (N:M) se resuelve con la tabla intermedia especialidad_medico.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        // minúsculas, sin tildes y sin espacios repetidos (IMMUTABLE para poder usarla en un índice)
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION f_normalizar(texto text) RETURNS text
            LANGUAGE sql IMMUTABLE STRICT AS
            $$ SELECT lower(regexp_replace(btrim(public.unaccent('public.unaccent'::regdictionary, texto)), '\s+', ' ', 'g')) $$;
        SQL);

        Schema::create('configuracion', function (Blueprint $table) {
            $table->string('clave', 40)->primary();
            $table->string('valor', 100);
            $table->string('descripcion', 200)->nullable();
            $table->timestamps();
        });
        DB::table('configuracion')->insert([
            'clave' => 'DURACION_FICHA_MIN', 'valor' => '15',
            'descripcion' => 'Minutos de atención por paciente (una ficha)', 'created_at' => now(), 'updated_at' => now(),
        ]);

        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        DB::statement('CREATE UNIQUE INDEX uq_especialidades_nombre ON especialidades (f_normalizar(nombre))');

        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 12)->unique();               // MED-0001
            $table->string('registro_profesional', 30)->unique();
            $table->string('nombres', 80);
            $table->string('apellidos', 80);
            $table->string('telefono', 20);
            $table->string('email', 120)->nullable();
            $table->boolean('activo')->default(true);
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
            $table->unsignedTinyInteger('dia_semana');              // 1 = lunes … 7 = domingo
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        DB::statement('ALTER TABLE horarios ADD CONSTRAINT chk_horario_rango CHECK (hora_fin > hora_inicio)');
        DB::statement('ALTER TABLE horarios ADD CONSTRAINT chk_horario_dia CHECK (dia_semana BETWEEN 1 AND 7)');

        // Feriados y bloqueos (RF05): medico_id NULL = toda la clínica; horas NULL = todo el día
        Schema::create('bloqueos_agenda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->nullable()->constrained('medicos')->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->string('tipo', 10);                             // FERIADO, VACACION, PERMISO, OTRO
            $table->string('motivo', 150);
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();
            $table->index(['fecha', 'medico_id']);
        });
        DB::statement("ALTER TABLE bloqueos_agenda ADD CONSTRAINT chk_bloqueo_tipo CHECK (tipo IN ('FERIADO','VACACION','PERMISO','OTRO'))");
        DB::statement("ALTER TABLE bloqueos_agenda ADD CONSTRAINT chk_bloqueo_rango CHECK (
            (hora_inicio IS NULL AND hora_fin IS NULL) OR (hora_inicio IS NOT NULL AND hora_fin IS NOT NULL AND hora_fin > hora_inicio))");
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos_agenda');
        Schema::dropIfExists('horarios');
        Schema::dropIfExists('especialidad_medico');
        Schema::dropIfExists('medicos');
        Schema::dropIfExists('especialidades');
        Schema::dropIfExists('configuracion');
        DB::statement('DROP FUNCTION IF EXISTS f_normalizar(text)');
    }
};
