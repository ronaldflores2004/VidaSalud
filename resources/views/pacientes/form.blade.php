@extends('layouts.app')
@php($editando = $paciente->exists)
@section('titulo', $editando ? 'Editar paciente' : 'Nuevo paciente')
@section('migas', 'Atención / Pacientes / ' . ($editando ? 'Editar' : 'Nuevo'))

@section('contenido')
<div class="cabecera"><div><h1>{{ $editando ? 'Editar paciente' : 'Registrar paciente' }}</h1><p class="sub">Código: <span class="mono">{{ $paciente->codigo }}</span> · Los campos con * son obligatorios.</p></div></div>

<form class="card form" method="POST" action="{{ $editando ? route('pacientes.update', $paciente) : route('pacientes.store') }}" data-validar novalidate>
    @csrf @if($editando) @method('PUT') @endif
    <h2>Datos personales</h2>
    <div class="grid-3">
        <label class="campo"><span>CI</span>
            <input name="ci" value="{{ old('ci', $paciente->ci) }}" maxlength="16" pattern="[0-9]{5,12}(-?[A-Za-z0-9]{1,3})?" data-msg="Entre 5 y 12 dígitos (ej. 8456123 o 8456123-1B)."></label>
        <label class="campo"><span>Nombres *</span>
            <input name="nombres" value="{{ old('nombres', $paciente->nombres) }}" required minlength="2" maxlength="80" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+" data-msg="Solo letras."></label>
        <label class="campo"><span>Apellidos *</span>
            <input name="apellidos" value="{{ old('apellidos', $paciente->apellidos) }}" required minlength="2" maxlength="80" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+" data-msg="Solo letras."></label>
        <label class="campo"><span>Fecha de nacimiento *</span>
            <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', $paciente->fecha_nacimiento?->format('Y-m-d')) }}" required max="{{ today()->toDateString() }}" min="1900-01-02"></label>
        <label class="campo"><span>Sexo *</span>
            <select name="sexo" required>
                <option value="">Seleccione…</option>
                @foreach(['F' => 'Femenino', 'M' => 'Masculino', 'O' => 'Otro'] as $k => $v)<option value="{{ $k }}" @selected(old('sexo', $paciente->sexo) === $k)>{{ $v }}</option>@endforeach
            </select></label>
        <label class="campo"><span>Tipo de sangre</span>
            <select name="tipo_sangre">
                <option value="">No registrado</option>
                @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $t)<option @selected(old('tipo_sangre', $paciente->tipo_sangre) === $t)>{{ $t }}</option>@endforeach
            </select></label>
    </div>
    <h2>Contacto</h2>
    <div class="grid-3">
        <label class="campo"><span>Teléfono *</span>
            <input name="telefono" value="{{ old('telefono', $paciente->telefono) }}" required pattern="[0-9+\s\-]{7,20}" data-msg="Entre 7 y 20 dígitos."></label>
        <label class="campo"><span>Correo electrónico</span>
            <input type="email" name="email" value="{{ old('email', $paciente->email) }}" maxlength="120"></label>
        <label class="campo"><span>Dirección</span>
            <input name="direccion" value="{{ old('direccion', $paciente->direccion) }}" maxlength="200"></label>
    </div>
    <h2>Información médica y emergencia</h2>
    <div class="grid-3">
        <label class="campo"><span>Alergias</span>
            <input name="alergias" value="{{ old('alergias', $paciente->alergias) }}" maxlength="255" placeholder="Ninguna conocida"></label>
        <label class="campo"><span>Contacto de emergencia</span>
            <input name="contacto_emergencia_nombre" value="{{ old('contacto_emergencia_nombre', $paciente->contacto_emergencia_nombre) }}" maxlength="120" placeholder="Nombre y parentesco"></label>
        <label class="campo"><span>Teléfono de emergencia</span>
            <input name="contacto_emergencia_telefono" value="{{ old('contacto_emergencia_telefono', $paciente->contacto_emergencia_telefono) }}" pattern="[0-9+\s\-]{7,20}" data-msg="Entre 7 y 20 dígitos."></label>
    </div>
    <div class="form-pie">
        <a href="{{ $editando ? route('pacientes.show', $paciente) : route('pacientes.index') }}" class="btn btn-ghost">Cancelar</a>
        <button class="btn btn-primario">Guardar paciente</button>
    </div>
</form>
@endsection
