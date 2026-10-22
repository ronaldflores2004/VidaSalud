@extends('layouts.app')
@section('titulo', 'Usuarios')
@section('migas', 'Administración / Usuarios')

@section('contenido')
<div class="cabecera">
    <div><h1>Usuarios del sistema</h1><p class="sub">Cuentas de acceso y rol asignado a cada persona.</p></div>
    <a href="{{ route('usuarios.create') }}" class="btn btn-primario">+ Nuevo usuario</a>
</div>

<form class="filtros" method="GET">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, usuario o correo">
    <select name="rol">
        <option value="">Todos los roles</option>
        @foreach($roles as $r)<option value="{{ $r->id }}" @selected(request('rol') == $r->id)>{{ $r->nombre }}</option>@endforeach
    </select>
    <button class="btn btn-sec">Buscar</button>
</form>

<div class="card">
    <table class="tabla">
        <thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse($usuarios as $usuario)
            <tr>
                <td><strong>{{ $usuario->name }}</strong></td>
                <td class="mono">{{ $usuario->username }}</td>
                <td>{{ $usuario->email }}</td>
                <td>{{ $usuario->rol->nombre }}</td>
                <td>{{ $usuario->ultimo_acceso?->format('d/m/Y H:i') ?? '—' }}</td>
                <td>@include('partials.estado', ['activo' => $usuario->activo])</td>
                <td class="acciones">
                    <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-ghost btn-sm">Editar</a>
                    <form method="POST" action="{{ route('usuarios.estado', $usuario) }}" data-confirmar="¿{{ $usuario->activo ? 'Desactivar' : 'Activar' }} la cuenta de {{ $usuario->name }}?">
                        @csrf @method('PATCH')
                        <button class="btn btn-ghost btn-sm">{{ $usuario->activo ? 'Desactivar' : 'Activar' }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="vacio">No se encontraron usuarios.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $usuarios->links('partials.paginacion') }}
</div>
@endsection
