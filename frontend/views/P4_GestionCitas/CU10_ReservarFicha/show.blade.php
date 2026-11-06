@extends('compartido.layouts.app')
@section('titulo', 'Cita ' . $cita->codigo)
@section('migas', 'Atención / Citas / ' . $cita->codigo)

@section('contenido')
<div class="cabecera">
    <div><h1>Cita médica {{ $cita->codigo }}</h1><p class="sub">Registrada el {{ $cita->created_at->format('d/m/Y H:i') }} por {{ $cita->registradoPor->name }}</p></div>
    @include('compartido.partials.estado-cita', ['estado' => $cita->estado])
</div>
<div class="ticket card">
    <div class="ticket-cab">
        <div><small>Fecha</small><strong>{{ ucfirst($cita->fecha->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}</strong></div>
        <div><small>Horario</small><strong class="mono">{{ $cita->horario }}</strong></div>
    </div>
    <dl class="datos datos-ancho">
        <dt>Paciente</dt><dd>{{ $cita->paciente->nombre_completo }} <span class="sub">({{ $cita->paciente->codigo }} · CI {{ $cita->paciente->ci ?? '—' }})</span></dd>
        <dt>Especialidad</dt><dd>{{ $cita->especialidad->nombre }}</dd>
        <dt>Modalidad</dt><dd>{{ ucfirst(strtolower($cita->modalidad)) }}</dd>
        <dt>Médico</dt><dd>{{ $cita->medico->nombre_completo }}</dd>
        <dt>Motivo</dt><dd>{{ $cita->motivo ?? '—' }}</dd>
    </dl>
</div>
{{-- CU21 Pagar ficha --}}
<div class="card pago-ficha">
    <div>
        <h2>Pago de la ficha</h2>
        @if($cita->pago)
            <p>@include('compartido.partials.estado-pago', ['cita' => $cita])
               <span class="sub">{{ $cita->pago->codigo }} · Bs {{ number_format($cita->pago->monto, 2) }}@if($cita->pago->monto_usd) (USD {{ number_format($cita->pago->monto_usd, 2) }})@endif · {{ $cita->pago->pagado_en->format('d/m/Y H:i') }}@if($cita->pago->referencia) · Ref. {{ $cita->pago->referencia }}@endif</span></p>
        @else
            <p>@include('compartido.partials.estado-pago', ['cita' => $cita]) <span class="sub">Precio de la ficha: Bs {{ number_format($cita->precio, 2) }}</span></p>
        @endif
    </div>
    @if(! $cita->pago && $cita->estaVigente() && ! $cita->fecha->lt(today()))
        @if(auth()->user()->tieneRol('Paciente'))
            <a href="{{ route('pagos.create', $cita) }}" class="btn btn-primario">Pagar ahora (CU21)</a>
        @elseif(auth()->user()->tieneRol('Administrador', 'Recepcionista'))
            @include('P7_PagosNotificaciones.CU21_PagarFicha._cobrar', ['cita' => $cita])
        @endif
    @endif
</div>
@unless(auth()->user()->tieneRol('Paciente'))
<div class="card">
    <h2>Historial de la cita</h2>
    <table class="tabla">
        <thead><tr><th>Fecha</th><th>Acción</th><th>Estado</th><th>Horario</th><th>Usuario</th><th>Motivo</th></tr></thead>
        <tbody>
        @foreach($cita->historial as $h)
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d/m/Y H:i') }}</td>
                <td>{{ ucfirst(strtolower($h->accion)) }}</td>
                <td>{{ $h->estado_anterior ? ucfirst(strtolower($h->estado_anterior)) . ' → ' : '' }}{{ ucfirst(strtolower($h->estado_nuevo)) }}</td>
                <td class="mono">{{ $h->fecha_nueva ? \Illuminate\Support\Carbon::parse($h->fecha_nueva)->format('d/m/Y') . ' ' . substr($h->hora_nueva, 0, 5) : '—' }}</td>
                <td>{{ $h->usuario?->name }}</td>
                <td>{{ $h->motivo ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endunless
<div class="botones">
    @php($u = auth()->user())
    <a href="{{ $u->tieneRol('Medico') ? route('agenda', ['fecha' => $cita->fecha->toDateString()]) : route('citas.index') }}" class="btn btn-ghost">Volver</a>
    @if($cita->estaVigente() && $cita->inicio->gt(now()))
        @if($u->tieneRol('Administrador', 'Recepcionista', 'Paciente'))
            <a href="{{ route('citas.reprogramar', $cita) }}" class="btn btn-sec">Reprogramar (CU11)</a>
        @endif
        @if($u->tieneRol('Administrador', 'Recepcionista', 'Paciente'))
            <a href="{{ route('citas.cancelar', $cita) }}" class="btn btn-ghost">Cancelar cita (CU12)</a>
        @endif
    @endif
    @if($u->tieneRol('Medico'))
        @if($cita->consulta)
            <a href="{{ route('consultas.show', $cita->consulta) }}" class="btn btn-primario">Ver consulta</a>
        @elseif($cita->estaVigente() && $cita->fecha->isToday())
            <a href="{{ route('consultas.create', $cita) }}" class="btn btn-primario">Atender (CU15)</a>
        @endif
    @endif
    @if($u->tieneRol('Administrador', 'Recepcionista'))
        <a href="{{ route('pacientes.show', $cita->paciente) }}" class="btn btn-ghost">Ver paciente</a>
    @endif
</div>
@endsection
