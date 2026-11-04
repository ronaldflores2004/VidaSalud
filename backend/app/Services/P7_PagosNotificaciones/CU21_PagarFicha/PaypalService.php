<?php

namespace App\Services\P7_PagosNotificaciones\CU21_PagarFicha;

use App\Exceptions\ReglaNegocioException;
use App\Models\P4_GestionCitas\Cita;
use Illuminate\Support\Facades\Http;

/**
 * Integración con la API REST de PayPal (Orders v2) – CU21 Pagar ficha.
 *
 * Flujo (todo del lado del servidor, así el monto no se puede alterar desde el navegador):
 *   1. token():      obtiene un token OAuth2 con el client_id y el secret de la app de PayPal.
 *   2. crearOrden(): crea la orden por el precio de la ficha en USD (PayPal no acepta bolivianos).
 *   3. El paciente aprueba el pago en la ventana de PayPal (botón del SDK de JavaScript).
 *   4. capturar():   cobra la orden y verifica estado COMPLETED, cita y monto.
 * En modo sandbox se usan cuentas de prueba: no se mueve dinero real.
 */
class PaypalService
{
    public function configurado(): bool
    {
        return filled(config('services.paypal.client_id')) && filled(config('services.paypal.secret'));
    }

    public function clientId(): ?string
    {
        return config('services.paypal.client_id');
    }

    private function token(): string
    {
        $r = Http::asForm()->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->timeout(15)->post(config('services.paypal.url') . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);
        if (! $r->successful()) {
            throw new ReglaNegocioException('No se pudo conectar con PayPal (revise PAYPAL_CLIENT_ID y PAYPAL_SECRET).');
        }

        return $r->json('access_token');
    }

    /** @return string id de la orden de PayPal */
    public function crearOrden(Cita $cita, float $montoUsd): string
    {
        $r = Http::withToken($this->token())->timeout(15)->post(config('services.paypal.url') . '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $cita->codigo,
                'custom_id' => (string) $cita->id,
                'description' => "Ficha {$cita->codigo} – {$cita->especialidad->nombre} – Clínica Vida Salud",
                'amount' => ['currency_code' => 'USD', 'value' => number_format($montoUsd, 2, '.', '')],
            ]],
        ]);
        if (! $r->successful() || ! $r->json('id')) {
            throw new ReglaNegocioException('PayPal no pudo crear la orden de pago. Intente nuevamente.');
        }

        return $r->json('id');
    }

    /** @return array{referencia:string, monto_usd:float} */
    public function capturar(string $ordenId, Cita $cita, float $montoUsd): array
    {
        $r = Http::withToken($this->token())->timeout(20)
            ->withBody('{}', 'application/json')
            ->post(config('services.paypal.url') . '/v2/checkout/orders/' . rawurlencode($ordenId) . '/capture');
        $unidad = $r->json('purchase_units.0');
        $captura = $unidad['payments']['captures'][0] ?? null;

        if (! $r->successful() || $r->json('status') !== 'COMPLETED' || ! $captura) {
            throw new ReglaNegocioException('El pago no fue completado en PayPal.');
        }
        if (($unidad['reference_id'] ?? null) !== $cita->codigo
            || abs((float) $captura['amount']['value'] - $montoUsd) > 0.009 || $captura['amount']['currency_code'] !== 'USD') {
            throw new ReglaNegocioException('El pago de PayPal no corresponde a esta ficha.');
        }

        return ['referencia' => $captura['id'], 'monto_usd' => (float) $captura['amount']['value']];
    }
}
