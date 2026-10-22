<?php

/**
 * Completa el modelo de datos con lo que pide el enunciado:
 *
 *  - pacientes.contacto_emergencia_parentesco  (perfil del paciente: nombre, parentesco y teléfono)
 *  - pacientes.acepta_notificaciones           (cuenta: notificaciones y preferencias)
 *  - citas.modalidad                           (gestión de citas: PRESENCIAL o VIRTUAL)
 *  - historial_citas   (P4) trazabilidad de cada cambio de una cita: reprogramación, cancelación, asistencia (RF08–RF12)
 *  - bloqueos_agenda   (P2) feriados y bloqueos de agenda, por médico o de toda la clínica (RF05)
 *  - notificaciones    (P4) recordatorios automáticos de próximas consultas
 *  - bitacora          (P1) auditoría: quién hizo qué, cuándo y desde dónde
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('contacto_emergencia_parentesco', 40)->nullable()->after('contacto_emergencia_nombre');
            $table->boolean('acepta_notificaciones')->default(true)->after('activo');
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->string('modalidad', 10)->default('PRESENCIAL')->after('hora_fin');
        });
        DB::statement("ALTER TABLE citas ADD CONSTRAINT chk_cita_modalidad CHECK (modalidad IN ('PRESENCIAL','VIRTUAL'))");

        // P4 – Cada cambio de una cita queda registrado (nunca se pierde la trazabilidad)
        Schema::create('historial_citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $table->string('accion', 15);                          // CREADA, REPROGRAMADA, CONFIRMADA, ATENDIDA, CANCELADA, INASISTENCIA
            $table->string('estado_anterior', 15)->nullable();
            $table->string('estado_nuevo', 15);
            $table->date('fecha_anterior')->nullable();
            $table->time('hora_anterior')->nullable();
            $table->date('fecha_nueva')->nullable();
            $table->time('hora_nueva')->nullable();
            $table->string('motivo', 255)->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['cita_id', 'created_at']);
        });
        DB::statement("ALTER TABLE historial_citas ADD CONSTRAINT chk_historial_accion CHECK (accion IN
            ('CREADA','REPROGRAMADA','CONFIRMADA','ATENDIDA','CANCELADA','INASISTENCIA'))");

        // P2 – Feriados y bloqueos: medico_id NULL = afecta a toda la clínica
        Schema::create('bloqueos_agenda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->nullable()->constrained('medicos')->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();              // NULL = todo el día
            $table->time('hora_fin')->nullable();
            $table->string('tipo', 10);                            // FERIADO, VACACION, PERMISO, OTRO
            $table->string('motivo', 150);
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();

            $table->index(['fecha', 'medico_id']);
        });
        DB::statement("ALTER TABLE bloqueos_agenda ADD CONSTRAINT chk_bloqueo_tipo CHECK (tipo IN ('FERIADO','VACACION','PERMISO','OTRO'))");
        DB::statement("ALTER TABLE bloqueos_agenda ADD CONSTRAINT chk_bloqueo_rango CHECK (
            (hora_inicio IS NULL AND hora_fin IS NULL) OR (hora_inicio IS NOT NULL AND hora_fin IS NOT NULL AND hora_fin > hora_inicio))");

        // P4 – Recordatorios de próximas consultas y avisos al paciente
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('cita_id')->nullable()->constrained('citas')->cascadeOnDelete();
            $table->string('tipo', 15);                            // RECORDATORIO, CANCELACION, REPROGRAMACION, RESULTADO
            $table->string('canal', 10)->default('SISTEMA');       // SISTEMA, EMAIL
            $table->string('mensaje', 255);
            $table->timestamp('programada_para');
            $table->timestamp('enviada_en')->nullable();
            $table->timestamp('leida_en')->nullable();
            $table->timestamps();

            $table->index(['paciente_id', 'leida_en']);
        });
        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT chk_notificacion_tipo CHECK (tipo IN ('RECORDATORIO','CANCELACION','REPROGRAMACION','RESULTADO'))");
        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT chk_notificacion_canal CHECK (canal IN ('SISTEMA','EMAIL'))");

        // P1 – Bitácora de auditoría
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 20);                          // LOGIN, LOGOUT, CREAR, MODIFICAR, DESACTIVAR…
            $table->string('modulo', 40);                          // P1_SeguridadAcceso, P4_GestionCitas…
            $table->string('tabla', 40)->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['tabla', 'registro_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('bloqueos_agenda');
        Schema::dropIfExists('historial_citas');
        DB::statement('ALTER TABLE citas DROP CONSTRAINT IF EXISTS chk_cita_modalidad');
        Schema::table('citas', fn (Blueprint $t) => $t->dropColumn('modalidad'));
        Schema::table('pacientes', fn (Blueprint $t) => $t->dropColumn(['contacto_emergencia_parentesco', 'acepta_notificaciones']));
    }
};
