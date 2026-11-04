<?php

/**
 * P7 Pagos y Notificaciones – pagos, suscripciones_push y ajustes en especialidades y notificaciones.
 *
 *  - especialidades.precio_consulta: costo de la ficha de esa especialidad (Bs).
 *  - pagos: un pago por cita (cita_id único). Guarda el monto cobrado en ese momento (dato
 *    histórico: si cambia el precio de la especialidad, el pago no cambia). Si no hay fila en
 *    pagos, la cita está pendiente de pago (RN14).
 *  - suscripciones_push: navegadores en los que el usuario activó las notificaciones push (CU22).
 *  - notificaciones: nuevo campo para_medico (el aviso es para el médico de la cita y no para el
 *    paciente); el destinatario se sigue obteniendo de la cita o del estudio (3FN).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('especialidades', function (Blueprint $table) {
            $table->decimal('precio_consulta', 8, 2)->default(150);
        });
        DB::statement('ALTER TABLE especialidades ADD CONSTRAINT chk_especialidad_precio CHECK (precio_consulta >= 0)');

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 12)->unique();                                   // PG-0001
            $table->foreignId('cita_id')->unique()->constrained('citas')->restrictOnDelete();
            $table->decimal('monto', 8, 2);                                           // en bolivianos
            $table->decimal('monto_usd', 8, 2)->nullable();                           // solo PayPal (no acepta BOB)
            $table->string('metodo', 10);                                             // PAYPAL, EFECTIVO, QR, TARJETA
            $table->string('estado', 15)->default('PAGADO');                          // PAGADO, POR_REEMBOLSAR, REEMBOLSADO
            $table->string('referencia', 60)->nullable();                             // id de captura PayPal o n.º de comprobante
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('pagado_en');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE pagos ADD CONSTRAINT chk_pago_metodo CHECK (metodo IN ('PAYPAL','EFECTIVO','QR','TARJETA'))");
        DB::statement("ALTER TABLE pagos ADD CONSTRAINT chk_pago_estado CHECK (estado IN ('PAGADO','POR_REEMBOLSAR','REEMBOLSADO'))");
        DB::statement('ALTER TABLE pagos ADD CONSTRAINT chk_pago_monto CHECK (monto > 0)');

        Schema::create('suscripciones_push', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('endpoint')->unique();
            $table->string('clave_p256dh', 120);
            $table->string('clave_auth', 40);
            $table->string('navegador', 120)->nullable();
            $table->timestamps();
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->boolean('para_medico')->default(false);
        });
        DB::statement('ALTER TABLE notificaciones DROP CONSTRAINT chk_notificacion_tipo');
        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT chk_notificacion_tipo CHECK (tipo IN ('RECORDATORIO','CANCELACION','REPROGRAMACION','RESULTADO','PAGO','NUEVA_CITA'))");
        DB::statement('ALTER TABLE notificaciones DROP CONSTRAINT chk_notificacion_canal');
        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT chk_notificacion_canal CHECK (canal IN ('SISTEMA','EMAIL','PUSH'))");

        DB::table('configuracion')->insertOrIgnore([
            'clave' => 'TIPO_CAMBIO_USD', 'valor' => '6.96',
            'descripcion' => 'Bolivianos por dólar para cobrar con PayPal (PayPal no acepta BOB).',
        ]);
    }

    public function down(): void
    {
        DB::table('configuracion')->where('clave', 'TIPO_CAMBIO_USD')->delete();
        DB::statement('ALTER TABLE notificaciones DROP CONSTRAINT chk_notificacion_tipo');
        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT chk_notificacion_tipo CHECK (tipo IN ('RECORDATORIO','CANCELACION','REPROGRAMACION','RESULTADO'))");
        DB::statement('ALTER TABLE notificaciones DROP CONSTRAINT chk_notificacion_canal');
        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT chk_notificacion_canal CHECK (canal IN ('SISTEMA','EMAIL'))");
        Schema::table('notificaciones', fn (Blueprint $t) => $t->dropColumn('para_medico'));
        Schema::dropIfExists('suscripciones_push');
        Schema::dropIfExists('pagos');
        DB::statement('ALTER TABLE especialidades DROP CONSTRAINT chk_especialidad_precio');
        Schema::table('especialidades', fn (Blueprint $t) => $t->dropColumn('precio_consulta'));
    }
};
