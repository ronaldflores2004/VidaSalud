@extends('compartido.layouts.app')
@section('titulo', 'Médicos')
@section('migas', 'Atención / Médicos')

@section('contenido')
<div class="cabecera">
    <div><h1>Médicos</h1><p class="sub">Profesionales, especialidades y estado.</p></div>
    @if(auth()->user()->tieneRol('Administrador'))
        <a href="{{ route('medicos.create') }}" class="btn btn-primario">+ Nuevo médico</a>
    @endif
</div>
<form class="filtros" method="GET">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o código">
    <select name="especialidad">
        <option value="">Todas las especialidades</option>
        @foreach($especialidades as $e)<option value="{{ $e->id }}" @selected(request('especialidad') == $e->id)>{{ $e->nombre }}</option>@endforeach
    </select>
    <button class="btn btn-sec">Buscar</button>
</form>
<div class="card">
    <table class="tabla">
        <thead><tr><th>Código</th><th>Médico</th><th>Reg. profesional</th><th>Especialidades</th><th>Teléfono</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse($medicos as $m)
            <tr>
                <td class="mono">{{ $m->codigo }}</td>
                <td><a href="{{ route('medicos.show', $m) }}"><strong>{{ $m->nombre_completo }}</strong></a></td>
                <td class="mono">{{ $m->registro_profesional }}</td>
                <td>@foreach($m->especialidades as $e)<span class="tag">{{ $e->nombre }}</span>@endforeach</td>
                <td>{{ $m->telefono }}</td>
                <td>@include('compartido.partials.estado', ['activo' => $m->activo])</td>
                <td class="acciones">
                    <a href="{{ route('medicos.show', $m) }}" class="btn btn-ghost btn-sm">Ver</a>
                    @if(auth()->user()->tieneRol('Administrador'))
                        <a href="{{ route('horarios.index', $m) }}" class="btn btn-ghost btn-sm">Horarios</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="vacio">No se encontraron médicos.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $medicos->links('compartido.partials.paginacion') }}
</div>
@endsection
