<?php

/*
| Comandos de consola del sistema (P7 Pagos y Notificaciones).
|   php artisan push:claves           → genera las claves VAPID para las notificaciones push (CU22)
|   php artisan notificaciones:enviar → envía las notificaciones programadas (recordatorios)
|   php artisan schedule:work         → deja corriendo el envío automático cada minuto
*/

use App\Services\P7_PagosNotificaciones\CU22_GestionarNotificaciones\NotificacionService;
use App\Support\WebPush;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('push:claves', function () {
    $k = WebPush::generarClaves();
    $this->info('Copie estas líneas en el archivo .env y luego ejecute: php artisan config:clear');
    $this->line('VAPID_PUBLIC_KEY=' . $k['public']);
    $this->line('VAPID_PRIVATE_KEY=' . $k['private']);
})->purpose('Genera las claves VAPID de las notificaciones push (CU22)');

Artisan::command('notificaciones:enviar', function (NotificacionService $servicio) {
    $total = $servicio->enviarPendientes();
    $this->info("Notificaciones procesadas: {$total}");
})->purpose('Envía por push las notificaciones programadas que ya vencieron (recordatorios de citas)');

Schedule::command('notificaciones:enviar')->everyMinute()->withoutOverlapping();
