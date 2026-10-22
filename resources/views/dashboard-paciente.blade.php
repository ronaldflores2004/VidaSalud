@extends('layouts.app')
@section('titulo', 'Inicio')
@section('migas', 'Inicio')

@section('contenido')
<div class="cabecera">
    <div><h1>Hola, {{ $paciente?->nombres ?? auth()->user()->name }}</h1>
        <p class="sub">{{ $paciente?->codigo }} · CI {{ $paciente?->ci ?? '—' }}</p></div>
    <a href="{{ route('citas.create') }}" class="btn btn-primario">+ Reservar ficha</a>
</div>
<div class="card">
    <div class="card-cab"><h2>Mis próximas fichas</h2><a href="{{ route('citas.index') }}" class="enlace">Ver todas mis citas</a></div>
    @forelse($proximas as $c)
        <a class="ficha" href="{{ route('citas.show', $c) }}">
            <div class="ficha-fecha"><strong>{{ $c->fecha->format('d') }}</strong><span>{{ $c->fecha->locale('es')->isoFormat('MMM') }}</span></div>
            <div><strong>{{ $c->especialidad->nombre }}</strong><span class="sub"> · {{ $c->medico->nombre_completo }}</span><br>
                <span class="mono">{{ $c->horario }}</span> · {{ $c->codigo }}</div>
            @include('partials.estado-cita', ['estado' => $c->estado])
        </a>
    @empty
        <p class="vacio">No tiene fichas reservadas. <a href="{{ route('citas.create') }}">Reserve una</a>.</p>
    @endforelse
</div>
<p class="ayuda">Su historial clínico, tratamientos y resultados de estudios estarán disponibles aquí en el Ciclo II.</p>
@endsection
