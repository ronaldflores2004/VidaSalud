@extends('compartido.layouts.app')
@section('titulo', 'Pagar ficha ' . $cita->codigo)
@section('migas', 'Mis fichas / ' . $cita->codigo . ' / Pagar')

@section('contenido')
<div class="cabecera">
    <div><h1>Pagar ficha {{ $cita->codigo }}</h1>
        <p class="sub">Pague su ficha desde su casa. Si prefiere, también puede pagarla en recepción antes de su consulta.</p></div>
    <span class="chip chip-pendiente-pago">Pendiente de pago</span>
</div>

<div class="pago-layout">
    <div class="ticket card">
        <div class="ticket-cab">
            <div><small>Fecha</small><strong>{{ ucfirst($cita->fecha->locale('es')->isoFormat('dddd D [de] MMMM')) }}</strong></div>
            <div><small>Horario</small><strong class="mono">{{ $cita->horario }}</strong></div>
        </div>
        <dl class="datos datos-ancho">
            <dt>Paciente</dt><dd>{{ $cita->paciente->nombre_completo }}</dd>
            <dt>Especialidad</dt><dd>{{ $cita->especialidad->nombre }}</dd>
            <dt>Médico</dt><dd>{{ $cita->medico->nombre_completo }}</dd>
        </dl>
    </div>

    <aside class="card pago-caja">
        <h2>Total a pagar</h2>
        <p class="pago-monto">Bs {{ number_format($cita->precio, 2) }}</p>
        <p class="sub">Con PayPal se cobra en dólares: <strong>USD {{ number_format($montoUsd, 2) }}</strong>
            (tipo de cambio {{ number_format($tipoCambio, 2) }} Bs por dólar).</p>

        @if($motivo)
            <div class="alerta alerta-error">{{ $motivo }}</div>
        @else
            <div id="pago-error" class="alerta alerta-error" hidden></div>
            @if($paypalClientId)
                <div id="paypal-botones" data-orden="{{ route('pagos.paypal.orden', $cita) }}" data-capturar="{{ route('pagos.paypal.capturar', $cita) }}"></div>
                <p class="sub pago-nota">Modo de pruebas de PayPal (sandbox): use una cuenta personal de prueba, no se cobra dinero real.</p>
            @else
                <div class="pago-aviso"><span><strong>PayPal aún no está configurado en este servidor.</strong>
                    Para demostraciones se usa un <em>pago de prueba simulado</em> que no se conecta con PayPal.</span></div>
                <button type="button" class="btn btn-primario btn-bloque" id="pago-simulado"
                        data-capturar="{{ route('pagos.paypal.capturar', $cita) }}">Pagar con PayPal (simulado)</button>
            @endif
        @endif
        <a href="{{ route('citas.show', $cita) }}" class="btn btn-ghost btn-bloque">Pagar después en recepción</a>
    </aside>
</div>
@endsection

@push('scripts')
@if($paypalClientId && ! $motivo)
<script src="https://www.paypal.com/sdk/js?client-id={{ urlencode($paypalClientId) }}&currency=USD&intent=capture&locale=es_ES"></script>
@endif
<script src="{{ url('assets/js/P7_PagosNotificaciones/CU21_PagarFicha/pagar.js') }}"></script>
@endpush
