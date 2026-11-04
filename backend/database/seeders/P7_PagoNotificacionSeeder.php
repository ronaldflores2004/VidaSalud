<?php

namespace Database\Seeders;

use App\Models\P1_SeguridadAcceso\User;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P7_PagosNotificaciones\Notificacion;
use App\Models\P7_PagosNotificaciones\Pago;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * P7 Pagos y Notificaciones – pagos de las fichas de ejemplo y avisos para el médico.
 *   - Citas atendidas: pagadas (efectivo, QR, tarjeta o PayPal).
 *   - Algunas inasistencias pagadas en línea y algunas cancelaciones ya reembolsadas.
 *   - Fichas de ejemplo: CM-0001 (María) pagada con PayPal; CM-0004 y CM-0005 (hoy) pagadas;
 *     CM-0002, CM-0003 y CM-0006 quedan PENDIENTES DE PAGO para probar el CU21.
 * Ejecutar solo este seeder:  php artisan db:seed --class=P7_PagoNotificacionSeeder
 */
class P7_PagoNotificacionSeeder extends Seeder
{
    public function run(): void
    {
        $this->callOnce([P4_CitaSeeder::class, P5_AtencionMedicaSeeder::class]);
        mt_srand(2026);
        $recepcion = User::where('username', 'recepcion')->value('id');
        $tc = 6.96;

        $pagar = function (Cita $cita, string $metodo, string $estado, $cuando, ?int $quien = null) use ($recepcion, $tc) {
            $monto = (float) $cita->especialidad->precio_consulta;
            Pago::updateOrCreate(['cita_id' => $cita->id], [
                'codigo' => Pago::where('cita_id', $cita->id)->value('codigo') ?? Pago::siguienteCodigo(),
                'monto' => $monto, 'monto_usd' => $metodo === 'PAYPAL' ? round($monto / $tc, 2) : null,
                'metodo' => $metodo, 'estado' => $estado,
                'referencia' => match ($metodo) {
                    'PAYPAL' => 'SIMULADO-' . Str::upper(Str::random(8)),
                    'QR' => 'QR-' . mt_rand(100000, 999999),
                    'TARJETA' => 'VOUCHER-' . mt_rand(1000, 9999),
                    default => null,
                },
                'registrado_por' => $quien ?? ($metodo === 'PAYPAL' ? ($cita->paciente->user_id ?? $recepcion) : $recepcion),
                'pagado_en' => $cuando,
            ]);
        };

        // Historial: las atendidas se pagaron; parte de las inasistencias y cancelaciones también
        Cita::with('especialidad', 'paciente')->whereNotIn('codigo', ['CM-0001', 'CM-0002', 'CM-0003', 'CM-0004', 'CM-0005', 'CM-0006'])
            ->orderBy('id')->get()->each(function (Cita $c) use ($pagar) {
                $metodos = ['EFECTIVO', 'EFECTIVO', 'QR', 'QR', 'TARJETA', 'PAYPAL'];
                $inicio = $c->inicio;
                match ($c->estado) {
                    Cita::ATENDIDA => $pagar($c, $metodos[mt_rand(0, 5)], Pago::PAGADO, $inicio->copy()->subMinutes(mt_rand(5, 30))),
                    Cita::INASISTENCIA => mt_rand(1, 2) === 1 ? $pagar($c, 'PAYPAL', Pago::PAGADO, $inicio->copy()->subDays(2)) : null,
                    Cita::CANCELADA => mt_rand(1, 3) === 1 ? $pagar($c, 'PAYPAL', Pago::REEMBOLSADO, $inicio->copy()->subDays(3)) : null,
                    default => null,
                };
            });

        // Fichas de ejemplo
        $ficha = fn ($codigo) => Cita::with('especialidad', 'paciente', 'medico')->where('codigo', $codigo)->first();
        if ($c = $ficha('CM-0001')) {
            $pagar($c, 'PAYPAL', Pago::PAGADO, now()->subHours(20));
            Notificacion::firstOrCreate(['cita_id' => $c->id, 'tipo' => 'PAGO'], [
                'mensaje' => "Recibimos su pago de Bs " . number_format($c->precio, 2) . " (PayPal) por la ficha {$c->codigo} del " . $c->fecha->format('d/m/Y') . '.',
                'programada_para' => now()->subHours(20), 'enviada_en' => now()->subHours(20),
            ]);
        }
        if ($c = $ficha('CM-0004')) {
            $pagar($c, 'PAYPAL', Pago::PAGADO, now()->subDay());
        }
        if ($c = $ficha('CM-0005')) {
            $pagar($c, 'EFECTIVO', Pago::PAGADO, now()->startOfDay()->addHours(7)->addMinutes(50));
        }

        // Avisos para el médico: nuevas fichas de hoy
        foreach (['CM-0004', 'CM-0005', 'CM-0006'] as $codigo) {
            if (($c = $ficha($codigo)) && $c->medico->user_id) {
                Notificacion::firstOrCreate(['cita_id' => $c->id, 'tipo' => 'NUEVA_CITA', 'para_medico' => true], [
                    'mensaje' => "Nueva ficha {$c->codigo}: {$c->paciente->nombre_completo}, " . $c->fecha->format('d/m/Y') . ' a las ' . substr($c->hora_inicio, 0, 5) . '.',
                    'programada_para' => now()->subDay(), 'enviada_en' => now()->subDay(),
                ]);
            }
        }
    }
}
