<?php

namespace App\Models\P1_SeguridadAcceso;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de auditoría: quién hizo qué, cuándo y desde qué IP.
 */
class Bitacora extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'bitacora';

    protected $fillable = ['user_id', 'accion', 'modulo', 'tabla', 'registro_id', 'descripcion', 'ip'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Registra una acción del usuario autenticado (o del indicado). */
    public static function registrar(string $accion, string $modulo, ?Model $registro = null, ?string $descripcion = null, ?int $userId = null): void
    {
        static::create([
            'user_id' => $userId ?? auth()->id(),
            'accion' => $accion,
            'modulo' => $modulo,
            'tabla' => $registro?->getTable(),
            'registro_id' => $registro?->getKey(),
            'descripcion' => $descripcion,
            'ip' => request()?->ip(),
        ]);
    }
}
