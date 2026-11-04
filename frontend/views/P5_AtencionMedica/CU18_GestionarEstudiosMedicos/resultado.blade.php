@extends('compartido.layouts.app')
@section('titulo', 'Resultado de estudio')
@section('migas', 'Mi consulta / Estudios / Resultado')

@section('contenido')
@php($paciente = $estudio->consulta->cita->paciente)
<div class="cabecera"><div><h1>Resultado: {{ $estudio->catalogo->nombre }}</h1>
    <p class="sub">{{ $paciente->nombre_completo }} · solicitado el {{ $estudio->fecha_solicitud->format('d/m/Y') }}{{ $estudio->indicaciones ? ' · ' . $estudio->indicaciones : '' }}</p></div></div>
<form class="card form estrecho" method="POST" action="{{ route('estudios.resultado.guardar', $estudio) }}" enctype="multipart/form-data" data-validar novalidate>
    @csrf @method('PUT')
    <div class="grid-2">
        <label class="campo"><span>Estado *</span>
            <select name="estado" required>
                <option value="REALIZADO" @selected(old('estado', $estudio->estado) === 'REALIZADO')>Realizado</option>
                <option value="ENTREGADO" @selected(old('estado', $estudio->estado) === 'ENTREGADO')>Entregado al paciente</option>
            </select></label>
        <label class="campo"><span>Fecha del resultado *</span>
            <input type="date" name="fecha_resultado" required min="{{ $estudio->fecha_solicitud->toDateString() }}" max="{{ today()->toDateString() }}"
                   value="{{ old('fecha_resultado', $estudio->fecha_resultado?->toDateString() ?? today()->toDateString()) }}"></label>
    </div>
    <label class="campo"><span>Resultado / interpretación *</span>
        <textarea name="resultado" rows="5" required minlength="5" maxlength="4000">{{ old('resultado', $estudio->resultado) }}</textarea></label>
    <label class="campo"><span>Archivo del resultado (PDF, JPG o PNG, máx. 4 MB)</span>
        <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png"></label>
    @if($estudio->archivo_resultado)<p class="ayuda">Ya tiene un archivo: <a href="{{ route('estudios.archivo', $estudio) }}">descargar</a>. Si sube otro, lo reemplaza.</p>@endif
    <div class="form-pie">
        <a href="{{ route('consultas.show', $estudio->consulta_id) }}" class="btn btn-ghost">Volver a la consulta</a>
        <button class="btn btn-primario">Guardar resultado</button>
    </div>
</form>
@endsection
