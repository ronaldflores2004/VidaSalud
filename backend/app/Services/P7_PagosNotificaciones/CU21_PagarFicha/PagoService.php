<?php

namespace App\Services\P7_PagosNotificaciones\CU21_PagarFicha;

use App\Exceptions\ReglaNegocioException;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P1_SeguridadAcceso\User;
use App\Models\P2_GestionMedica\Configuracion;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P7_PagosNotificaciones\Pago;
use App\Services\P7_PagosNotificaciones\CU22_GestionarNotificaciones\NotificacionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Capa de negocio – CU21 Pagar ficha (RF14).
 *  - El paciente paga en línea con PayPal al reservar su ficha.
 *  - La recepcionista cobra en la clínica (efectivo, QR o tarjeta) las fichas pendientes.
 *  - RN14: una ficha se paga una sola vez y solo mientras la cita está vigente; sin pago no se
 *    registra la llegada (CU13) ni se atiende la consulta (CU15).
 *  - RN15: si una ficha pagada se cancela, el pago queda por reembolsar hasta que recepción lo devuelve.
 */
class PagoService
{
    public function tipoCambio(): float
    {
        return (float) Configuracion::valor('TIPO_CAMBIO_USD', 6.96);
    }

    public function montoUsd(Cita $cita): float
    {
        return round($cita->precio / $this->tipoCambio(), 2);
    }

    /** Devuelve el motivo por el que no se puede pagar, o null si se puede. */
    public function motivoNoPagable(Cita $cita): ?string
    {
        if ($cita->pago()->exists()) {
            return 'Esta ficha ya está pagada.';
        }
        if (! $cita->estaVigente()) {
            return 'Solo se pagan fichas vigentes (estado actual: ' . strtolower($cita->estado) . ').';
        }
        if ($cita->fecha->lt(today())) {
            return 'La fecha de la cita ya pasó.';
        }

        return null;
    }

    public function registrar(Cita $cita, string $metodo, ?string $referencia, User $usuario, ?float $montoUsd = null): Pago
    {
        try {
            $pago = DB::transaction(function () use ($cita, $metodo, $referencia, $usuario, $montoUsd) {
                $cita = Cita::with('especialidad', 'paciente')->whereKey($cita->id)->lockForUpdate()->firstOrFail();
                if ($motivo = $this->motivoNoPagable($cita)) {
                    throw new ReglaNegocioException($motivo);
                }

                $pago = Pago::create([
                    'codigo' => Pago::siguienteCodigo(), 'cita_id' => $cita->id, 'monto' => $cita->precio,
                    'monto_usd' => $montoUsd, 'metodo' => $metodo, 'estado' => Pago::PAGADO,
                    'referencia' => $referencia, 'registrado_por' => $usuario->id, 'pagado_en' => now(),
                ]);

                if ($cita->paciente->acepta_notificaciones) {
                    app(NotificacionService::class)->crear([
                        'cita_id' => $cita->id, 'tipo' => 'PAGO',
                        'mensaje' => "Recibimos su pago de Bs " . number_format($pago->monto, 2) . " ({$pago->metodo_texto}) por la ficha {$cita->codigo} del " . $cita->fecha->format('d/m/Y') . '.',
                    ]);
                }
                Bitacora::registrar('CREAR', 'P7_PagosNotificaciones', $pago, "Pago {$pago->codigo} de la cita {$cita->codigo} ({$metodo})", $usuario->id);

                return $pago;
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {       // cita_id único: otro pago se registró al mismo tiempo
                throw new ReglaNegocioException('Esta ficha ya está pagada.');
            }
            throw $e;
        }

        return $pago;
    }

    /** RN15: recepción devuelve el dinero de una ficha pagada que fue cancelada. */
    public function reembolsar(Pago $pago, User $usuario): Pago
    {
        if ($pago->estado !== Pago::POR_REEMBOLSAR) {
            throw new ReglaNegocioException('Solo se reembolsan pagos de fichas canceladas.');
        }
        $pago->update(['estado' => Pago::REEMBOLSADO]);
        Bitacora::registrar('MODIFICAR', 'P7_PagosNotificaciones', $pago, "Pago {$pago->codigo} reembolsado", $usuario->id);

        return $pago;
    }
}
