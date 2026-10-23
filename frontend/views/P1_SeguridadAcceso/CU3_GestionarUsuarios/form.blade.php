@extends('compartido.layouts.app')
@php($editando = $usuario->exists)
@section('titulo', $editando ? 'Editar usuario' : 'Nuevo usuario')
@section('migas', 'Administración / Usuarios / ' . ($editando ? 'Editar' : 'Nuevo'))

@section('contenido')
<div class="cabecera"><div><h1>{{ $editando ? 'Editar usuario' : 'Registrar usuario' }}</h1><p class="sub">Los campos con * son obligatorios.</p></div></div>

<form class="card form" method="POST" action="{{ $editando ? route('usuarios.update', $usuario) : route('usuarios.store') }}" data-validar novalidate>
    @csrf
    @if($editando) @method('PUT') @endif
    <div class="grid-2">
        <label class="campo"><span>Nombre completo *</span>
            <input name="name" value="{{ old('name', $usuario->name) }}" required minlength="3" maxlength="120"></label>
        <label class="campo"><span>Rol *</span>
            <select name="rol_id" required>
                <option value="">Seleccione…</option>
                @foreach($roles as $r)<option value="{{ $r->id }}" @selected(old('rol_id', $usuario->rol_id) == $r->id)>{{ $r->nombre }}</option>@endforeach
            </select></label>
        <label class="campo"><span>Nombre de usuario *</span>
            <input name="username" value="{{ old('username', $usuario->username) }}" required minlength="4" maxlength="40" pattern="[A-Za-z0-9_\-\.]+" data-msg="Solo letras, números, guiones y puntos."></label>
        <label class="campo"><span>Correo electrónico *</span>
            <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required maxlength="120"></label>
        <label class="campo"><span>Contraseña {{ $editando ? '(dejar vacío para no cambiar)' : '*' }}</span>
            <input type="password" name="password" {{ $editando ? '' : 'required' }} minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" data-msg="Mínimo 8 caracteres con letras y números." autocomplete="new-password"></label>
        <label class="campo"><span>Confirmar contraseña</span>
            <input type="password" name="password_confirmation" data-igual="password" autocomplete="new-password"></label>
    </div>
    <div class="form-pie">
        <a href="{{ route('usuarios.index') }}" class="btn btn-ghost">Cancelar</a>
        <button class="btn btn-primario">Guardar</button>
    </div>
</form>
@endsection
