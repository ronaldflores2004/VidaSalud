<?php

namespace App\Models\P7_PagosNotificaciones;

use App\Models\P1_SeguridadAcceso\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Navegador en el que un usuario activó las notificaciones push (CU22).
 * endpoint: dirección del servicio push del navegador (Google, Mozilla, Apple…);
 * clave_p256dh y clave_auth: claves del navegador para cifrar el mensaje.
 */
class SuscripcionPush extends Model
{
    protected $table = 'suscripciones_push';

    protected $fillable = ['user_id', 'endpoint', 'clave_p256dh', 'clave_auth', 'navegador'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
