@extends('layouts.app')
@section('titulo', 'Cita ' . $cita->codigo)
@section('migas', 'Atención / Citas / ' . $cita->codigo)

@section('contenido')
<div class="cabecera">
    <div><h1>Cita médica {{ $cita->codigo }}</h1><p class="sub">Registrada el {{ $cita->created_at->format('d/m/Y H:i') }} por {{ $cita->registradoPor->name }}</p></div>
    @include('partials.estado-cita', ['estado' => $cita->estado])
</div>
<div class="ticket card">
    <div class="ticket-cab">
        <div><small>Fecha</small><strong>{{ ucfirst($cita->fecha->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}</strong></div>
        <div><small>Horario</small><strong class="mono">{{ $cita->horario }}</strong></div>
    </div>
    <dl class="datos datos-ancho">
        <dt>Paciente</dt><dd>{{ $cita->paciente->nombre_completo }} <span class="sub">({{ $cita->paciente->codigo }} · CI {{ $cita->paciente->ci ?? '—' }})</span></dd>
        <dt>Especialidad</dt><dd>{{ $cita->especialidad->nombre }}</dd>
        <dt>Médico</dt><dd>{{ $cita->medico->nombre_completo }}</dd>
        <dt>Motivo</dt><dd>{{ $cita->motivo ?? '—' }}</dd>
        @if($cita->motivo_cancelacion)<dt>Motivo de cancelación</dt><dd>{{ $cita->motivo_cancelacion }}</dd>@endif
    </dl>
</div>
<div class="botones">
    <a href="{{ route('citas.index') }}" class="btn btn-ghost">Volver a citas</a>
    @if(auth()->user()->tieneRol('Administrador', 'Recepcionista'))
        <a href="{{ route('pacientes.show', $cita->paciente) }}" class="btn btn-sec">Ver paciente</a>
        <a href="{{ route('citas.create') }}" class="btn btn-primario">Programar otra cita</a>
    @endif
</div>
@endsection
