<?php

namespace App\Services\P7_PagosNotificaciones\CU22_GestionarNotificaciones;

use App\Models\P7_PagosNotificaciones\Notificacion;
use App\Models\P7_PagosNotificaciones\SuscripcionPush;
use App\Support\WebPush;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Capa de negocio – CU22 Gestionar notificaciones (RF15).
 *
 *  - crear(): guarda la notificación (bandeja del sistema). Si ya corresponde enviarla, la manda
 *    por push después de responder al usuario, para no hacerlo esperar.
 *  - enviar(): cifra el aviso y lo envía a cada navegador suscrito del destinatario (RN16).
 *    Si el servicio push responde 404/410, la suscripción venció y se elimina.
 *  - enviarPendientes(): la usa el comando programado notificaciones:enviar para los
 *    recordatorios del día anterior.
 * Un fallo de red al enviar el push nunca deshace la operación principal (reserva, pago, etc.).
 */
class NotificacionService
{
    public function crear(array $datos): Notificacion
    {
        $n = Notificacion::create($datos + ['programada_para' => now(), 'canal' => 'SISTEMA']);

        if ($n->programada_para->lte(now()) && WebPush::desdeConfig()) {
            $id = $n->id;
            dispatch(fn () => app(self::class)->enviar(Notificacion::find($id)))->afterResponse();
        }

        return $n;
    }

    /** @return int cantidad de navegadores que aceptaron el mensaje */
    public function enviar(?Notificacion $n): int
    {
        if (! $n || $n->enviada_en) {
            return 0;
        }
        $entregadas = 0;
        $usuario = $n->destinatario();
        $push = WebPush::desdeConfig();

        if ($usuario && $push) {
            $mensaje = json_encode([
                'titulo' => 'Clínica Vida Salud · ' . $n->tipo_texto,
                'cuerpo' => $n->mensaje,
                'url' => $n->url,
                'etiqueta' => 'notificacion-' . $n->id,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            foreach ($usuario->suscripcionesPush as $s) {
                $entregadas += $this->enviarA($push, $s, $mensaje) ? 1 : 0;
            }
        }

        $n->update(['enviada_en' => now(), 'canal' => $entregadas ? 'PUSH' : $n->canal]);

        return $entregadas;
    }

    /** Envío de prueba desde la bandeja (botón «Probar notificación»). */
    public function probar(SuscripcionPush $s): bool
    {
        $push = WebPush::desdeConfig();

        return $push && $this->enviarA($push, $s, json_encode([
            'titulo' => 'Clínica Vida Salud', 'cuerpo' => 'Las notificaciones push están activas en este navegador.',
            'url' => route('notificaciones.index'), 'etiqueta' => 'prueba',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function enviarPendientes(): int
    {
        $total = 0;
        Notificacion::whereNull('enviada_en')->where('programada_para', '<=', now())
            ->orderBy('programada_para')->limit(200)->get()
            ->each(function ($n) use (&$total) {
                $this->enviar($n);
                $total++;
            });

        return $total;
    }

    private function enviarA(WebPush $push, SuscripcionPush $s, string $mensaje): bool
    {
        try {
            $estado = $push->enviar($s->endpoint, $s->clave_p256dh, $s->clave_auth, $mensaje);
            if (in_array($estado, [404, 410], true)) {
                $s->delete();   // el usuario desactivó o borró la suscripción en su navegador
            }

            return $estado >= 200 && $estado < 300;
        } catch (Throwable $e) {
            Log::warning('Push no enviado: ' . $e->getMessage());

            return false;
        }
    }
}
