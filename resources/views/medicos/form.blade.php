@extends('layouts.app')
@php($editando = $medico->exists)
@section('titulo', $editando ? 'Editar médico' : 'Nuevo médico')
@section('migas', 'Atención / Médicos / ' . ($editando ? 'Editar' : 'Nuevo'))

@section('contenido')
@php($seleccionadas = old('especialidades', $medico->especialidades?->pluck('id')->all() ?? []))
<div class="cabecera"><div><h1>{{ $editando ? 'Editar médico' : 'Registrar médico' }}</h1><p class="sub">Código asignado: <span class="mono">{{ $medico->codigo }}</span></p></div></div>

<form class="card form" method="POST" action="{{ $editando ? route('medicos.update', $medico) : route('medicos.store') }}" data-validar novalidate>
    @csrf @if($editando) @method('PUT') @endif
    <div class="grid-2">
        <label class="campo"><span>Nombres *</span>
            <input name="nombres" value="{{ old('nombres', $medico->nombres) }}" required minlength="2" maxlength="80" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+" data-msg="Solo letras."></label>
        <label class="campo"><span>Apellidos *</span>
            <input name="apellidos" value="{{ old('apellidos', $medico->apellidos) }}" required minlength="2" maxlength="80" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+" data-msg="Solo letras."></label>
        <label class="campo"><span>N.º de registro profesional *</span>
            <input name="registro_profesional" value="{{ old('registro_profesional', $medico->registro_profesional) }}" required maxlength="30"></label>
        <label class="campo"><span>Teléfono *</span>
            <input name="telefono" value="{{ old('telefono', $medico->telefono) }}" required pattern="[0-9+\s\-]{7,20}" data-msg="Entre 7 y 20 dígitos."></label>
        <label class="campo"><span>Correo electrónico</span>
            <input type="email" name="email" value="{{ old('email', $medico->email) }}" maxlength="120"></label>
        <label class="campo"><span>Cuenta de acceso (rol Médico)</span>
            <select name="user_id">
                <option value="">Sin cuenta</option>
                @foreach($cuentas as $c)<option value="{{ $c->id }}" @selected(old('user_id', $medico->user_id) == $c->id)>{{ $c->name }} ({{ $c->username }})</option>@endforeach
            </select></label>
    </div>
    <fieldset class="campo">
        <span>Especialidades *</span>
        <div class="checks" data-requerir-uno="Seleccione al menos una especialidad.">
            @foreach($especialidades as $e)
                <label class="check-card"><input type="checkbox" name="especialidades[]" value="{{ $e->id }}" @checked(in_array($e->id, $seleccionadas))> {{ $e->nombre }}</label>
            @endforeach
        </div>
    </fieldset>
    <div class="form-pie">
        <a href="{{ $editando ? route('medicos.show', $medico) : route('medicos.index') }}" class="btn btn-ghost">Cancelar</a>
        <button class="btn btn-primario">Guardar</button>
    </div>
</form>
@endsection
