/**
 * Service worker – CU22 Notificaciones push de la Clínica Vida Salud.
 * Lo ejecuta el navegador en segundo plano, incluso con la página cerrada.
 *  - push:              el navegador ya descifró el mensaje; se muestra la notificación.
 *  - notificationclick: al tocarla se abre (o enfoca) la pantalla correspondiente.
 */
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));

self.addEventListener('push', (evento) => {
    let datos = {};
    try {
        datos = evento.data ? evento.data.json() : {};
    } catch (e) {
        datos = { cuerpo: evento.data ? evento.data.text() : '' };
    }
    const titulo = datos.titulo || 'Clínica Vida Salud';
    evento.waitUntil(self.registration.showNotification(titulo, {
        body: datos.cuerpo || 'Tiene una nueva notificación.',
        tag: datos.etiqueta || undefined,
        icon: '/assets/img/icono.png',
        badge: '/assets/img/icono.png',
        data: { url: datos.url || '/notificaciones' },
        lang: 'es',
    }));
});

self.addEventListener('notificationclick', (evento) => {
    evento.notification.close();
    const destino = new URL(evento.notification.data.url, self.location.origin).href;
    evento.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((ventanas) => {
        for (const v of ventanas) {
            if (v.url === destino && 'focus' in v) return v.focus();
        }
        return self.clients.openWindow(destino);
    }));
});
