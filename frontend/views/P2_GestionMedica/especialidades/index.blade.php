@extends('compartido.layouts.app')
@section('titulo', 'Especialidades')
@section('migas', 'Administración / Especialidades')

@section('contenido')
<div class="cabecera">
    <div><h1>Especialidades médicas</h1><p class="sub">Servicios que ofrece la clínica.</p></div>
    <a href="{{ route('especialidades.create') }}" class="btn btn-primario">+ Nueva especialidad</a>
</div>
<form class="filtros" method="GET">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar especialidad">
    <button class="btn btn-sec">Buscar</button>
</form>
<div class="card">
    <table class="tabla">
        <thead><tr><th>Nombre</th><th>Descripción</th><th>Médicos</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse($especialidades as $e)
            <tr>
                <td><strong>{{ $e->nombre }}</strong></td>
                <td>{{ $e->descripcion ?? '—' }}</td>
                <td>{{ $e->medicos_count }}</td>
                <td>@include('compartido.partials.estado', ['activo' => $e->activo])</td>
                <td class="acciones">
                    <a href="{{ route('especialidades.edit', $e) }}" class="btn btn-ghost btn-sm">Editar</a>
                    <form method="POST" action="{{ route('especialidades.estado', $e) }}" data-confirmar="¿{{ $e->activo ? 'Desactivar' : 'Activar' }} {{ $e->nombre }}?">
                        @csrf @method('PATCH')<button class="btn btn-ghost btn-sm">{{ $e->activo ? 'Desactivar' : 'Activar' }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="vacio">No hay especialidades registradas.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $especialidades->links('compartido.partials.paginacion') }}
</div>
@endsection
