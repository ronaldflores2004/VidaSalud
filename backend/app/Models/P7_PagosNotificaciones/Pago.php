<?php

namespace App\Models\P7_PagosNotificaciones;

use App\Models\P1_SeguridadAcceso\User;
use App\Models\P4_GestionCitas\Cita;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entidad Pago (CU21). Una cita tiene como máximo un pago (cita_id único).
 * Se paga en línea con PayPal (en dólares) o en recepción con efectivo, QR o tarjeta.
 */
class Pago extends Model
{
    public const PAGADO = 'PAGADO';
    public const POR_REEMBOLSAR = 'POR_REEMBOLSAR';
    public const REEMBOLSADO = 'REEMBOLSADO';
    public const ESTADOS = ['PAGADO' => 'Pagado', 'POR_REEMBOLSAR' => 'Por reembolsar', 'REEMBOLSADO' => 'Reembolsado'];
    public const METODOS = ['PAYPAL' => 'PayPal', 'EFECTIVO' => 'Efectivo', 'QR' => 'QR bancario', 'TARJETA' => 'Tarjeta'];

    protected $fillable = ['codigo', 'cita_id', 'monto', 'monto_usd', 'metodo', 'estado', 'referencia', 'registrado_por', 'pagado_en'];

    protected $casts = ['monto' => 'decimal:2', 'monto_usd' => 'decimal:2', 'pagado_en' => 'datetime'];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function getMetodoTextoAttribute(): string
    {
        return self::METODOS[$this->metodo] ?? $this->metodo;
    }

    public static function siguienteCodigo(): string
    {
        return sprintf('PG-%04d', (int) static::max('id') + 1);
    }
}
