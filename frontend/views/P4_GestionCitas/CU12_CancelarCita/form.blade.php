@extends('compartido.layouts.app')
@section('titulo', 'Cancelar ' . $cita->codigo)
@section('migas', 'Citas / ' . $cita->codigo . ' / Cancelar')

@section('contenido')
<div class="cabecera"><div><h1>Cancelar cita {{ $cita->codigo }}</h1>
    <p class="sub">La cita no se elimina: queda como cancelada con su motivo y la ficha se libera para otro paciente.</p></div></div>
<form class="card form estrecho" method="POST" action="{{ route('citas.cancelar.guardar', $cita) }}" data-validar novalidate data-confirmar="¿Confirma que desea cancelar la cita {{ $cita->codigo }}?">
    @csrf
    <dl class="datos datos-ancho">
        <dt>Paciente</dt><dd>{{ $cita->paciente->nombre_completo }}</dd>
        <dt>Médico</dt><dd>{{ $cita->medico->nombre_completo }} · {{ $cita->especialidad->nombre }}</dd>
        <dt>Fecha y hora</dt><dd><strong>{{ $cita->fecha->format('d/m/Y') }}</strong> <span class="mono">{{ $cita->horario }}</span></dd>
    </dl>
    <label class="campo" style="margin-top:1rem"><span>Motivo de la cancelación *</span>
        <textarea name="motivo" rows="3" required minlength="5" maxlength="255" data-msg="Escriba el motivo (mínimo 5 caracteres).">{{ old('motivo') }}</textarea></label>
    <div class="form-pie">
        <a href="{{ route('citas.show', $cita) }}" class="btn btn-ghost">Volver sin cancelar</a>
        <button class="btn btn-peligro" type="submit">Cancelar la cita</button>
    </div>
</form>
@endsection
