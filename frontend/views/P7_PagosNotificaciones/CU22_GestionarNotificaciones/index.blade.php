@extends('compartido.layouts.app')
@section('titulo', 'Notificaciones')
@section('migas', 'Notificaciones')

@section('contenido')
<div class="cabecera">
    <div><h1>Notificaciones</h1>
        <p class="sub">Avisos de sus citas, pagos y resultados. Active las notificaciones push para recibirlos aunque no tenga abierta esta página.</p></div>
    @if($noLeidas)
        <form method="POST" action="{{ route('notificaciones.leidas') }}">@csrf<button class="btn btn-sec">Marcar {{ $noLeidas }} como leídas</button></form>
    @endif
</div>

<div class="card push-card" id="push"
     data-clave="{{ $clavePublica }}" data-sw="{{ url('sw.js') }}"
     data-suscribir="{{ route('notificaciones.suscribir') }}" data-probar="{{ route('notificaciones.probar') }}">
    <div class="push-icono" aria-hidden="true">🔔</div>
    <div class="push-texto">
        <h2>Notificaciones push en este navegador</h2>
        <p class="sub" data-push-estado>Comprobando…</p>
    </div>
    <div class="push-botones">
        <button type="button" class="btn btn-primario" data-push-activar hidden>Activar notificaciones</button>
        <button type="button" class="btn btn-sec" data-push-probar hidden>Probar notificación</button>
        <button type="button" class="btn btn-ghost" data-push-desactivar hidden>Desactivar</button>
    </div>
</div>

<div class="card">
    <ul class="bandeja">
    @forelse($notificaciones as $n)
        <li class="{{ $n->leida_en ? '' : 'no-leida' }}">
            <span class="chip chip-notif-{{ strtolower($n->tipo) }}">{{ $n->tipo_texto }}</span>
            <div>
                <a href="{{ $n->url }}">{{ $n->mensaje }}</a>
                <small class="sub">{{ $n->programada_para->locale('es')->diffForHumans() }} · {{ $n->programada_para->format('d/m/Y H:i') }}
                    @if($n->canal === 'PUSH') · enviada por push @endif</small>
            </div>
        </li>
    @empty
        <li class="vacio">No tiene notificaciones.</li>
    @endforelse
    </ul>
    {{ $notificaciones->links('compartido.partials.paginacion') }}
</div>
@endsection

@push('scripts')
<script src="{{ url('assets/js/P7_PagosNotificaciones/CU22_GestionarNotificaciones/push.js') }}"></script>
@endpush
