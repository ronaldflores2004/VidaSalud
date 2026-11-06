@extends('compartido.layouts.app')
@section('titulo', 'Reprogramar ' . $cita->codigo)
@section('migas', (auth()->user()->tieneRol('Paciente') ? 'Mis citas / ' : 'Atención / Citas / ') . $cita->codigo . ' / Reprogramar')

@section('contenido')
<div class="cabecera"><div><h1>Reprogramar cita {{ $cita->codigo }}</h1>
    <p class="sub">Se mantiene el mismo médico y especialidad. Solo se muestran las fichas libres.
        Política: se reprograma hasta {{ $horasMin }} horas antes; a esta cita le quedan <strong>{{ $restantes }}</strong> reprogramación(es) (RN18).</p></div>
    @include('compartido.partials.estado-cita', ['estado' => $cita->estado])
</div>

<form method="POST" action="{{ route('citas.reprogramar.guardar', $cita) }}" id="form-reprogramar" class="cita-layout" data-validar novalidate
      data-url-bloques="{{ url('api/medicos/' . $cita->medico_id . '/bloques') }}" data-cita="{{ $cita->id }}">
    @csrf @method('PUT')
    <div class="cita-pasos">
        <section class="card paso">
            <h2><span class="num">1</span> Cita actual</h2>
            <dl class="datos datos-ancho">
                <dt>Paciente</dt><dd>{{ $cita->paciente->nombre_completo }} <span class="sub">({{ $cita->paciente->codigo }})</span></dd>
                <dt>Médico</dt><dd>{{ $cita->medico->nombre_completo }} · {{ $cita->especialidad->nombre }}</dd>
                <dt>Fecha y hora actual</dt><dd><strong>{{ $cita->fecha->format('d/m/Y') }}</strong> <span class="mono">{{ $cita->horario }}</span></dd>
            </dl>
        </section>
        <section class="card paso">
            <h2><span class="num">2</span> Nueva fecha y hora</h2>
            <label class="campo estrecho"><span>Nueva fecha *</span>
                <input type="date" name="fecha" id="fecha" required min="{{ today()->toDateString() }}" value="{{ old('fecha') }}"></label>
            <input type="hidden" name="hora_inicio" id="hora_inicio" value="{{ old('hora_inicio') }}" required data-msg="Seleccione el nuevo horario.">
            <div id="bloques" class="bloques"><p class="ayuda">Elija la nueva fecha para ver las fichas libres del médico.</p></div>
            <label class="campo"><span>Motivo de la reprogramación *</span>
                <input name="motivo" required minlength="5" maxlength="255" value="{{ old('motivo') }}" placeholder="Ej. El paciente pidió cambiar el día" data-msg="Escriba el motivo (mínimo 5 caracteres)."></label>
        </section>
    </div>
    <aside class="card resumen">
        <h2>Nuevo horario</h2>
        <dl class="datos">
            <dt>Antes</dt><dd>{{ $cita->fecha->format('d/m/Y') }} {{ substr($cita->hora_inicio, 0, 5) }}</dd>
            <dt>Fecha</dt><dd id="r-fecha">—</dd>
            <dt>Hora</dt><dd id="r-hora">—</dd>
        </dl>
        <button class="btn btn-primario btn-bloque" type="submit">Guardar reprogramación</button>
        <a href="{{ route('citas.show', $cita) }}" class="btn btn-ghost btn-bloque">Volver sin cambios</a>
    </aside>
</form>
@endsection

@push('scripts')
<script src="{{ url('assets/js/P4_GestionCitas/CU11_ReprogramarCita/reprogramar.js') }}"></script>
@endpush
