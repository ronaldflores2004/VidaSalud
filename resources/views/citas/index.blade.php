@extends('layouts.app')
@php($esMedico = auth()->user()->tieneRol('Medico'))
@php($esPaciente = auth()->user()->tieneRol('Paciente'))
@section('titulo', $esMedico ? 'Mi agenda' : ($esPaciente ? 'Mis citas' : 'Citas'))
@section('migas', $esMedico ? 'Mi consulta / Agenda' : ($esPaciente ? 'Mis fichas / Mis citas' : 'Atención / Citas'))

@section('contenido')
<div class="cabecera">
    <div><h1>{{ $esMedico ? 'Mi agenda' : ($esPaciente ? 'Mis citas' : 'Citas médicas') }}</h1><p class="sub">{{ $esMedico ? 'Solo se muestran sus pacientes.' : 'Filtre por fecha, médico y estado.' }}</p></div>
    @unless($esMedico)<a href="{{ route('citas.create') }}" class="btn btn-primario">+ Programar cita</a>@endunless
</div>
<form class="filtros" method="GET">
    <input type="date" name="fecha" value="{{ request('fecha') }}">
    @unless($esMedico || $esPaciente)
    <select name="medico">
        <option value="">Todos los médicos</option>
        @foreach($medicos as $m)<option value="{{ $m->id }}" @selected(request('medico') == $m->id)>{{ $m->nombre_completo }}</option>@endforeach
    </select>
    @endunless
    <select name="estado">
        <option value="">Todos los estados</option>
        @foreach($estados as $e)<option value="{{ $e }}" @selected(request('estado') === $e)>{{ ucfirst(strtolower($e)) }}</option>@endforeach
    </select>
    <button class="btn btn-sec">Filtrar</button>
    @if(request()->hasAny(['fecha','medico','estado']))<a href="{{ route('citas.index') }}" class="btn btn-ghost">Limpiar</a>@endif
</form>
<div class="card">
    <table class="tabla">
        <thead><tr><th>Código</th><th>Fecha</th><th>Horario</th><th>Paciente</th><th>Médico</th><th>Especialidad</th><th>Estado</th></tr></thead>
        <tbody>
        @forelse($citas as $c)
            <tr>
                <td><a href="{{ route('citas.show', $c) }}"><strong>{{ $c->codigo }}</strong></a></td>
                <td>{{ $c->fecha->format('d/m/Y') }}</td>
                <td class="mono">{{ $c->horario }}</td>
                <td>{{ $c->paciente->nombre_completo }}</td>
                <td>{{ $c->medico->nombre_completo }}</td>
                <td>{{ $c->especialidad->nombre }}</td>
                <td>@include('partials.estado-cita', ['estado' => $c->estado])</td>
            </tr>
        @empty
            <tr><td colspan="7" class="vacio">No hay citas con esos filtros.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $citas->links('partials.paginacion') }}
</div>
@endsection
