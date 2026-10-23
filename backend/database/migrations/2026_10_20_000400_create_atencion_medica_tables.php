<?php

/**
 * P5 Atención Médica e Historial Clínico (y notificaciones al paciente).
 * Tablas creadas desde el Ciclo I; pantallas y lógica en el Ciclo II.
 *
 * Normalización:
 *  - consultas ya no guarda medico_id ni historia_clinica_id: se obtienen de la cita
 *    (cita → médico, cita → paciente → historia). Evita dependencias transitivas (3FN).
 *  - Medicamentos y estudios son catálogos (medicamentos, catalogo_estudios); tratamientos y
 *    estudios solo los referencian, así el nombre no se repite ni se escribe distinto.
 *  - notificaciones apunta a la cita o al estudio; el paciente se obtiene de ellos.
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
            $table->timestamp('fecha_hora');
            $table->string('motivo_consulta', 255);
            $table->decimal('peso_kg', 5, 2)->nullable();
            $table->decimal('talla_cm', 5, 1)->nullable();
            $table->string('presion_arterial', 10)->nullable();            // 120/80
            $table->decimal('temperatura_c', 4, 1)->nullable();
            $table->unsignedSmallInteger('frecuencia_cardiaca')->nullable();
            $table->text('examen_fisico')->nullable();
            $table->text('diagnostico');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE consultas ADD CONSTRAINT chk_consulta_temperatura CHECK (temperatura_c IS NULL OR temperatura_c BETWEEN 30 AND 45)");

        Schema::create('medicamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);                                 // Paracetamol
            $table->string('presentacion', 60);                            // Tabletas 500 mg
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['nombre', 'presentacion']);
        });

        Schema::create('tratamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained('consultas')->cascadeOnDelete();
            $table->foreignId('medicamento_id')->constrained('medicamentos')->restrictOnDelete();
            $table->string('dosis', 60);                                   // 1 tableta
            $table->string('frecuencia', 60);                              // cada 8 horas
            $table->string('duracion', 60);                                // 7 días
            $table->string('indicaciones', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('catalogo_estudios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();                       // Hemograma completo
            $table->string('categoria', 15);                               // LABORATORIO, IMAGEN, OTRO
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        DB::statement("ALTER TABLE catalogo_estudios ADD CONSTRAINT chk_catalogo_categoria CHECK (categoria IN ('LABORATORIO','IMAGEN','OTRO'))");

        // El médico puede mandar varios estudios en una consulta
        Schema::create('estudios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained('consultas')->cascadeOnDelete();
            $table->foreignId('catalogo_estudio_id')->constrained('catalogo_estudios')->restrictOnDelete();
            $table->string('indicaciones', 255)->nullable();
            $table->string('estado', 12)->default('SOLICITADO');
            $table->date('fecha_solicitud');
            $table->date('fecha_resultado')->nullable();
            $table->text('resultado')->nullable();
            $table->string('archivo_resultado', 255)->nullable();          // ruta del PDF o imagen
            $table->timestamps();
        });
        DB::statement("ALTER TABLE estudios ADD CONSTRAINT chk_estudio_estado CHECK (estado IN ('SOLICITADO','REALIZADO','ENTREGADO'))");
        DB::statement("ALTER TABLE estudios ADD CONSTRAINT chk_estudio_fechas CHECK (fecha_resultado IS NULL OR fecha_resultado >= fecha_solicitud)");

        // Recordatorios y avisos: se refieren a una cita o a un estudio (el paciente se obtiene de ellos)
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->nullable()->constrained('citas')->cascadeOnDelete();
            $table->foreignId('estudio_id')->nullable()->constrained('estudios')->cascadeOnDelete();
            $table->string('tipo', 15);                                    // RECORDATORIO, CANCELACION, REPROGRAMACION, RESULTADO
            $table->string('canal', 10)->default('SISTEMA');               // SISTEMA, EMAIL
            $table->string('mensaje', 255);
            $table->timestamp('programada_para');
            $table->timestamp('enviada_en')->nullable();
            $table->timestamp('leida_en')->nullable();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT chk_notificacion_tipo CHECK (tipo IN ('RECORDATORIO','CANCELACION','REPROGRAMACION','RESULTADO'))");
        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT chk_notificacion_canal CHECK (canal IN ('SISTEMA','EMAIL'))");
        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT chk_notificacion_origen CHECK ((cita_id IS NULL) <> (estudio_id IS NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('estudios');
        Schema::dropIfExists('catalogo_estudios');
        Schema::dropIfExists('tratamientos');
        Schema::dropIfExists('medicamentos');
        Schema::dropIfExists('consultas');
        Schema::dropIfExists('historias_clinicas');
    }
};
