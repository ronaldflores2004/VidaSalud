/**
 * CU22 Gestionar notificaciones – activar / desactivar notificaciones push en el navegador.
 * 1. Registra el service worker (sw.js), que recibe los mensajes aunque la página esté cerrada.
 * 2. Pide permiso al usuario (Notification.requestPermission).
 * 3. Crea la suscripción con la clave pública VAPID del servidor (pushManager.subscribe).
 * 4. Envía la suscripción al servidor, que la guarda en la tabla suscripciones_push.
 */
(function () {
    const caja = document.getElementById('push');
    if (!caja) return;
    const estado = caja.querySelector('[data-push-estado]');
    const btnActivar = caja.querySelector('[data-push-activar]');
    const btnProbar = caja.querySelector('[data-push-probar]');
    const btnDesactivar = caja.querySelector('[data-push-desactivar]');
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    const ver = (activa) => {
        btnActivar.hidden = activa;
        btnProbar.hidden = !activa;
        btnDesactivar.hidden = !activa;
    };
    const mensaje = (texto) => { estado.textContent = texto; };

    function claveABytes(base64url) {
        const b64 = (base64url + '='.repeat((4 - base64url.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(atob(b64), (c) => c.charCodeAt(0));
    }

    async function enviar(url, metodo, datos) {
        const r = await fetch(url, {
            method: metodo,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify(datos || {}),
        });
        const json = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(json.message || 'Error del servidor');
        return json;
    }

    async function iniciar() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            mensaje('Este navegador no admite notificaciones push. Use Chrome, Edge o Firefox actualizados.');
            return;
        }
        if (!window.isSecureContext) {
            mensaje('Las notificaciones push necesitan HTTPS (o abrir el sistema como localhost).');
            return;
        }
        if (!caja.dataset.clave) {
            mensaje('El servidor todavía no tiene claves VAPID (ejecute: php artisan push:claves).');
            return;
        }
        const registro = await navigator.serviceWorker.register(caja.dataset.sw, { scope: '/' });
        const suscripcion = await registro.pushManager.getSubscription();
        if (Notification.permission === 'denied') {
            mensaje('Bloqueó las notificaciones para este sitio. Habilítelas en la configuración del navegador (icono del candado).');
            ver(false);
            btnActivar.hidden = true;
            return;
        }
        ver(!!suscripcion);
        mensaje(suscripcion ? 'Activadas: recibirá los avisos aunque cierre esta página.' : 'Desactivadas en este navegador.');
    }

    btnActivar.addEventListener('click', async () => {
        try {
            const permiso = await Notification.requestPermission();
            if (permiso !== 'granted') {
                mensaje('No se concedió el permiso para mostrar notificaciones.');
                return;
            }
            const registro = await navigator.serviceWorker.ready;
            const suscripcion = await registro.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: claveABytes(caja.dataset.clave),
            });
            const r = await enviar(caja.dataset.suscribir, 'POST', suscripcion.toJSON());
            ver(true);
            mensaje(r.mensaje);
        } catch (e) {
            mensaje('No se pudo activar: ' + e.message);
        }
    });

    btnDesactivar.addEventListener('click', async () => {
        const registro = await navigator.serviceWorker.ready;
        const suscripcion = await registro.pushManager.getSubscription();
        if (suscripcion) {
            await enviar(caja.dataset.suscribir, 'DELETE', { endpoint: suscripcion.endpoint }).catch(() => null);
            await suscripcion.unsubscribe();
        }
        ver(false);
        mensaje('Desactivadas en este navegador.');
    });

    btnProbar.addEventListener('click', async () => {
        try {
            const r = await enviar(caja.dataset.probar, 'POST');
            mensaje(r.mensaje);
        } catch (e) {
            mensaje('No se pudo enviar la prueba: ' + e.message);
        }
    });

    iniciar().catch((e) => mensaje('No se pudo iniciar: ' + e.message));
})();
