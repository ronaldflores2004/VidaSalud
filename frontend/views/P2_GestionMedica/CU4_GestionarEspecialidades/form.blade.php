@extends('compartido.layouts.app')
@php($editando = $especialidad->exists)
@section('titulo', $editando ? 'Editar especialidad' : 'Nueva especialidad')
@section('migas', 'Administración / Especialidades / ' . ($editando ? 'Editar' : 'Nueva'))

@section('contenido')
<div class="cabecera"><div><h1>{{ $editando ? 'Editar especialidad' : 'Registrar especialidad' }}</h1></div></div>
<form class="card form estrecho" method="POST" action="{{ $editando ? route('especialidades.update', $especialidad) : route('especialidades.store') }}" data-validar novalidate>
    @csrf @if($editando) @method('PUT') @endif
    <label class="campo"><span>Nombre *</span>
        <input name="nombre" value="{{ old('nombre', $especialidad->nombre) }}" required minlength="3" maxlength="80" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s\-\.]+" data-msg="Solo letras y espacios."></label>
    <label class="campo"><span>Descripción</span>
        <textarea name="descripcion" rows="3" maxlength="255">{{ old('descripcion', $especialidad->descripcion) }}</textarea></label>
    <label class="campo"><span>Precio de la ficha (Bs) *</span>
        <input type="number" name="precio_consulta" value="{{ old('precio_consulta', $especialidad->precio_consulta ?? 150) }}" required min="0" max="9999" step="0.50"></label>
    <div class="form-pie">
        <a href="{{ route('especialidades.index') }}" class="btn btn-ghost">Cancelar</a>
        <button class="btn btn-primario">Guardar</button>
    </div>
</form>
@endsection
