<?php

namespace App\Models\P2_GestionMedica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Parámetros generales del sistema (clave → valor), p. ej. DURACION_FICHA_MIN = 15.
 * Normalización: un valor único del sistema se guarda una sola vez.
 */
class Configuracion extends Model
{
    protected $table = 'configuracion';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['clave', 'valor', 'descripcion'];

    public static function valor(string $clave, $defecto = null)
    {
        return Cache::remember("config.$clave", 300, fn () => static::find($clave)?->valor ?? $defecto);
    }

    /** Cambia un parámetro y limpia su caché para que se use de inmediato. */
    public static function guardar(string $clave, string $valor): void
    {
        static::whereKey($clave)->update(['valor' => $valor]);
        Cache::forget("config.$clave");
    }
}
