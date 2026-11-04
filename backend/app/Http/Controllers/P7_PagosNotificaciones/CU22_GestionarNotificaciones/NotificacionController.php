<?php

namespace App\Http\Controllers\P7_PagosNotificaciones\CU22_GestionarNotificaciones;

use App\Http\Controllers\Controller;
use App\Models\P7_PagosNotificaciones\Notificacion;
use App\Models\P7_PagosNotificaciones\SuscripcionPush;
use App\Services\P7_PagosNotificaciones\CU22_GestionarNotificaciones\NotificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CU22 Gestionar notificaciones (paciente y médico).
 *  - Bandeja con los avisos del usuario y opción de marcarlos como leídos.
 *  - Activar / desactivar las notificaciones push en el navegador (suscripción Web Push).
 *  - Enviar una notificación de prueba.
 *  - Sirve el service worker (sw.js) que recibe y muestra las notificaciones.
 */
class NotificacionController extends Controller
{
    public function __construct(private NotificacionService $servicio)
    {
    }

    public function index(Request $request): View
    {
        $usuario = $request->user();

        return view('P7_PagosNotificaciones.CU22_GestionarNotificaciones.index', [
            'notificaciones' => Notificacion::delUsuario($usuario)->with('cita', 'estudio.catalogo')
                ->orderByDesc('programada_para')->paginate(15),
            'noLeidas' => Notificacion::delUsuario($usuario)->whereNull('leida_en')->count(),
            'clavePublica' => config('services.webpush.public_key'),
            'suscripciones' => $usuario->suscripcionesPush()->count(),
        ]);
    }

    public function marcarLeidas(Request $request): RedirectResponse
    {
        Notificacion::delUsuario($request->user())->whereNull('leida_en')->update(['leida_en' => now()]);

        return back()->with('ok', 'Notificaciones marcadas como leídas.');
    }

    /** Guarda la suscripción push del navegador (la envía el JavaScript al activar). */
    public function suscribir(Request $request): JsonResponse
    {
        $datos = $request->validate([
            // Los servicios push reales usan HTTPS; en desarrollo se acepta un servicio de prueba local
            'endpoint' => ['required', 'url', 'max:1000', app()->isProduction() ? 'starts_with:https://' : 'regex:#^(https://|http://(127\.0\.0\.1|localhost)[:/])#'],
            'keys.p256dh' => ['required', 'string', 'max:120'],
            'keys.auth' => ['required', 'string', 'max:40'],
        ]);
        SuscripcionPush::updateOrCreate(['endpoint' => $datos['endpoint']], [
            'user_id' => $request->user()->id,
            'clave_p256dh' => $datos['keys']['p256dh'],
            'clave_auth' => $datos['keys']['auth'],
            'navegador' => Str::limit((string) $request->userAgent(), 115, ''),
        ]);

        return response()->json(['ok' => true, 'mensaje' => 'Notificaciones push activadas en este navegador.']);
    }

    public function desuscribir(Request $request): JsonResponse
    {
        $request->user()->suscripcionesPush()->where('endpoint', (string) $request->input('endpoint'))->delete();

        return response()->json(['ok' => true, 'mensaje' => 'Notificaciones push desactivadas en este navegador.']);
    }

    public function probar(Request $request): JsonResponse
    {
        $enviadas = $request->user()->suscripcionesPush->filter(fn ($s) => $this->servicio->probar($s))->count();

        return response()->json(['ok' => $enviadas > 0, 'mensaje' => $enviadas
            ? "Notificación de prueba enviada a {$enviadas} navegador(es)."
            : 'No se pudo enviar: active las notificaciones y verifique la conexión a internet.']);
    }

    /** Service worker en la raíz del sitio para que controle todas las páginas. */
    public function serviceWorker(): Response
    {
        $js = file_get_contents(dirname(config('view.paths')[0]) . '/assets/js/P7_PagosNotificaciones/CU22_GestionarNotificaciones/sw.js');

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
