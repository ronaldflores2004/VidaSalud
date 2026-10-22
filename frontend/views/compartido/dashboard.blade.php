@extends('compartido.layouts.app')
@section('titulo', 'Inicio')
@section('migas', 'Inicio')

@section('contenido')
<div class="cabecera">
    <div>
        <h1>Hola, {{ explode(' ', auth()->user()->name)[0] }}</h1>
        <p class="sub">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}</p>
    </div>
    @if(auth()->user()->tieneRol('Administrador', 'Recepcionista'))
        <a href="{{ route('citas.create') }}" class="btn btn-primario">+ Programar cita</a>
    @endif
</div>

<div class="kpis">
    <div class="kpi"><span>Citas de hoy</span><strong>{{ $totales['citasHoy'] }}</strong></div>
    <div class="kpi"><span>Próximas citas</span><strong>{{ $totales['proximas'] }}</strong></div>
    <div class="kpi"><span>Pacientes activos</span><strong>{{ $totales['pacientes'] }}</strong></div>
    <div class="kpi"><span>Médicos activos</span><strong>{{ $totales['medicos'] }}</strong></div>
</div>

<div class="card">
    <div class="card-cab"><h2>Agenda de hoy</h2><a href="{{ route('citas.index', ['fecha' => today()->toDateString()]) }}" class="enlace">Ver todas</a></div>
    @if($citasHoy->isEmpty())
        <p class="vacio">No hay citas programadas para hoy.</p>
    @else
        <table class="tabla">
            <thead><tr><th>Hora</th><th>Código</th><th>Paciente</th><th>Médico</th><th>Especialidad</th><th>Estado</th></tr></thead>
            <tbody>
            @foreach($citasHoy as $c)
                <tr>
                    <td class="mono">{{ $c->horario }}</td>
                    <td><a href="{{ route('citas.show', $c) }}">{{ $c->codigo }}</a></td>
                    <td>{{ $c->paciente->nombre_completo }}</td>
                    <td>{{ $c->medico->nombre_completo }}</td>
                    <td>{{ $c->especialidad->nombre }}</td>
                    <td>@include('compartido.partials.estado-cita', ['estado' => $c->estado])</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
