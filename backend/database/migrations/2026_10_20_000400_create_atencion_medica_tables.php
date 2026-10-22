<?php

/**
 * Paquete P5: Atención Médica e Historial Clínico (tablas creadas en el Ciclo I,
 * pantallas y lógica en el Ciclo II).
 *
 *  historias_clinicas  1 por paciente: antecedentes, alergias, enfermedades crónicas.
 *  consultas           1 por cita atendida: signos vitales, examen y diagnóstico.
 *  tratamientos        0..* por consulta: medicamento, dosis, frecuencia, duración.
 *  estudios            0..* por consulta: el médico puede mandar varios estudios y registrar su resultado.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historias_clinicas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 12)->unique();                       // HC-0001
            $table->foreignId('paciente_id')->unique()->constrained('pacientes')->restrictOnDelete();
            $table->text('antecedentes_personales')->nullable();
            $table->text('antecedentes_familiares')->nullable();
            $table->string('alergias', 255)->nullable();
            $table->string('enfermedades_cronicas', 255)->nullable();
            $table->string('medicacion_habitual', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->unique()->constrained('citas')->restrictOnDelete();
            $table->foreignId('historia_clinica_id')->constrained('historias_clinicas')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->timestamp('fecha_hora');
            $table->string('motivo', 255);
            $table->decimal('peso_kg', 5, 2)->nullable();
            $table->decimal('talla_cm', 5, 1)->nullable();
            $table->string('presion_arterial', 10)->nullable();            // 120/80
            $table->decimal('temperatura_c', 4, 1)->nullable();
            $table->unsignedSmallInteger('frecuencia_cardiaca')->nullable();
            $table->text('examen_fisico')->nullable();
            $table->text('diagnostico');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['historia_clinica_id', 'fecha_hora']);
        });

        Schema::create('tratamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained('consultas')->cascadeOnDelete();
            $table->string('medicamento', 120);
            $table->string('dosis', 60);                                   // 500 mg
            $table->string('frecuencia', 60);                              // cada 8 horas
            $table->string('duracion', 60);                                // 7 días
            $table->string('indicaciones', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('estudios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained('consultas')->cascadeOnDelete();
            $table->string('tipo', 15);                                    // LABORATORIO, IMAGEN, OTRO
            $table->string('nombre', 120);                                 // Hemograma completo, Rayos X de tórax…
            $table->string('indicaciones', 255)->nullable();
            $table->string('estado', 12)->default('SOLICITADO');
            $table->date('fecha_solicitud');
            $table->date('fecha_resultado')->nullable();
            $table->text('resultado')->nullable();
            $table->string('archivo_resultado', 255)->nullable();          // ruta del PDF o imagen
            $table->timestamps();
        });

        DB::statement("ALTER TABLE consultas ADD CONSTRAINT chk_consulta_temperatura CHECK (temperatura_c IS NULL OR temperatura_c BETWEEN 30 AND 45)");
        DB::statement("ALTER TABLE estudios ADD CONSTRAINT chk_estudio_tipo CHECK (tipo IN ('LABORATORIO','IMAGEN','OTRO'))");
        DB::statement("ALTER TABLE estudios ADD CONSTRAINT chk_estudio_estado CHECK (estado IN ('SOLICITADO','REALIZADO','ENTREGADO'))");
        DB::statement("ALTER TABLE estudios ADD CONSTRAINT chk_estudio_fechas CHECK (fecha_resultado IS NULL OR fecha_resultado >= fecha_solicitud)");
    }

    public function down(): void
    {
        Schema::dropIfExists('estudios');
        Schema::dropIfExists('tratamientos');
        Schema::dropIfExists('consultas');
        Schema::dropIfExists('historias_clinicas');
    }
};
