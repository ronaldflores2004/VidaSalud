{{-- CU21: cobro de la ficha en recepción (efectivo, QR o tarjeta). Requiere $cita. --}}
<form method="POST" action="{{ route('pagos.cobrar', $cita) }}" class="cobro" data-validar novalidate>
    @csrf
    <select name="metodo" required aria-label="Método de pago" data-metodo>
        <option value="EFECTIVO">Efectivo</option>
        <option value="QR">QR bancario</option>
        <option value="TARJETA">Tarjeta</option>
    </select>
    <input name="referencia" maxlength="60" placeholder="N.º comprobante" aria-label="Número de comprobante" data-referencia hidden>
    <button class="btn btn-primario btn-sm">Cobrar Bs {{ number_format($cita->precio, 2) }}</button>
</form>
