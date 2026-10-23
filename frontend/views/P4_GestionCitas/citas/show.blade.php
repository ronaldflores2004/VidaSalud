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
    <a href="{{ route('citas.index') }}" class="btn btn-ghost">Volver a {{ auth()->user()->tieneRol('Paciente') ? 'mis citas' : 'citas' }}</a>
    @if(auth()->user()->tieneRol('Administrador', 'Recepcionista'))
        <a href="{{ route('pacientes.show', $cita->paciente) }}" class="btn btn-sec">Ver paciente</a>
        <a href="{{ route('citas.create') }}" class="btn btn-primario">Programar otra cita</a>
    @endif
</div>
@endsection
