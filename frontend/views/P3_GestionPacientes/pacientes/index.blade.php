@extends('compartido.layouts.app')
@section('titulo', 'Pacientes')
@section('migas', 'Atención / Pacientes')

@section('contenido')
<div class="cabecera">
    <div><h1>Pacientes</h1><p class="sub">Busque por CI, nombre, apellido o código.</p></div>
    <a href="{{ route('pacientes.create') }}" class="btn btn-primario">+ Nuevo paciente</a>
</div>
<form class="filtros" method="GET">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="CI, nombre, apellido o código" autofocus>
    <select name="estado">
        <option value="">Todos</option>
        <option value="1" @selected(request('estado') === '1')>Activos</option>
        <option value="0" @selected(request('estado') === '0')>Inactivos</option>
    </select>
    <button class="btn btn-sec">Buscar</button>
</form>
<div class="card">
    <table class="tabla">
        <thead><tr><th>Código</th><th>CI</th><th>Paciente</th><th>Edad</th><th>Teléfono</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse($pacientes as $p)
            <tr>
                <td class="mono">{{ $p->codigo }}</td>
                <td class="mono">{{ $p->ci ?? '—' }}</td>
                <td><a href="{{ route('pacientes.show', $p) }}"><strong>{{ $p->apellidos }}, {{ $p->nombres }}</strong></a></td>
                <td>{{ $p->edad }} años</td>
                <td>{{ $p->telefono }}</td>
                <td>@include('compartido.partials.estado', ['activo' => $p->activo])</td>
                <td class="acciones">
                    <a href="{{ route('pacientes.show', $p) }}" class="btn btn-ghost btn-sm">Ver</a>
                    @if($p->activo)<a href="{{ route('citas.create', ['paciente' => $p->id]) }}" class="btn btn-ghost btn-sm">Programar cita</a>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="vacio">No se encontraron pacientes{{ request('q') ? ' para «' . request('q') . '»' : '' }}.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $pacientes->links('compartido.partials.paginacion') }}
</div>
@endsection
