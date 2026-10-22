@extends('layouts.app')
@section('titulo', $esPaciente ? 'Reservar ficha' : 'Programar cita')
@section('migas', $esPaciente ? 'Mis fichas / Reservar' : 'Atención / Programar cita')

@section('contenido')
<div class="cabecera"><div><h1>{{ $esPaciente ? 'Reservar ficha médica' : 'Programar cita médica' }}</h1><p class="sub">Solo se muestran los horarios libres del médico. La disponibilidad se verifica otra vez al guardar.</p></div></div>

<form method="POST" action="{{ route('citas.store') }}" id="form-cita" class="cita-layout" data-validar novalidate
      data-url-medicos="{{ url('api/especialidades') }}" data-url-bloques="{{ url('api/medicos') }}" data-url-pacientes="{{ $esPaciente ? '' : route('api.pacientes') }}">
    @csrf
    <div class="cita-pasos">
        {{-- Paso 1: paciente (el paciente que reserva desde la web ya está identificado) --}}
        @if($esPaciente)
        <section class="card paso">
            <h2><span class="num">1</span> Paciente</h2>
            <input type="hidden" name="paciente_id" id="paciente_id" value="{{ $pacienteSeleccionado->id }}">
            <p><strong>{{ $pacienteSeleccionado->nombre_completo }}</strong> <span class="sub">· CI {{ $pacienteSeleccionado->ci ?? '—' }} · {{ $pacienteSeleccionado->codigo }}</span></p>
            <p class="ayuda">Cada ficha es de 15 minutos. Llegue 10 minutos antes de su horario.</p>
        </section>
        @else
        <section class="card paso">
            <h2><span class="num">1</span> Paciente</h2>
            <input type="hidden" name="paciente_id" id="paciente_id" value="{{ old('paciente_id', $pacienteSeleccionado?->id) }}" required data-msg="Busque y seleccione un paciente.">
            <div class="buscador">
                <input type="search" id="buscar-paciente" placeholder="Escriba CI, nombre o apellido (mín. 2 letras)" autocomplete="off"
                       value="{{ $pacienteSeleccionado ? $pacienteSeleccionado->nombre_completo : '' }}">
                <ul class="sugerencias" id="sugerencias" hidden></ul>
            </div>
            <p class="ayuda">¿Paciente nuevo? <a href="{{ route('pacientes.create') }}">Regístrelo primero</a>.</p>
        </section>
        @endif

        {{-- Paso 2: especialidad y médico --}}
        <section class="card paso">
            <h2><span class="num">2</span> Especialidad y médico</h2>
            <div class="grid-2">
                <label class="campo"><span>Especialidad *</span>
                    <select name="especialidad_id" id="especialidad_id" required>
                        <option value="">Seleccione…</option>
                        @foreach($especialidades as $e)<option value="{{ $e->id }}" @selected(old('especialidad_id') == $e->id)>{{ $e->nombre }}</option>@endforeach
                    </select></label>
                <label class="campo"><span>Médico *</span>
                    <select name="medico_id" id="medico_id" required disabled data-old="{{ old('medico_id') }}">
                        <option value="">Primero elija la especialidad</option>
                    </select></label>
            </div>
            <p class="ayuda" id="dias-medico"></p>
        </section>

        {{-- Paso 3: fecha y hora --}}
        <section class="card paso">
            <h2><span class="num">3</span> Fecha y hora</h2>
            <label class="campo estrecho"><span>Fecha *</span>
                <input type="date" name="fecha" id="fecha" required min="{{ today()->toDateString() }}" value="{{ old('fecha') }}"></label>
            <input type="hidden" name="hora_inicio" id="hora_inicio" value="{{ old('hora_inicio') }}" required data-msg="Seleccione un horario disponible.">
            <div id="bloques" class="bloques"><p class="ayuda">Elija médico y fecha para ver los horarios.</p></div>
            <label class="campo"><span>Motivo de la consulta (opcional)</span>
                <input name="motivo" maxlength="255" value="{{ old('motivo') }}" placeholder="Ej. Control general"></label>
        </section>
    </div>

    {{-- Resumen de la cita --}}
    <aside class="card resumen">
        <h2>Resumen</h2>
        <dl class="datos">
            <dt>Paciente</dt><dd id="r-paciente">{{ $pacienteSeleccionado?->nombre_completo ?? '—' }}</dd>
            <dt>Especialidad</dt><dd id="r-especialidad">—</dd>
            <dt>Médico</dt><dd id="r-medico">—</dd>
            <dt>Fecha</dt><dd id="r-fecha">—</dd>
            <dt>Horario</dt><dd id="r-hora">—</dd>
        </dl>
        <button class="btn btn-primario btn-bloque" type="submit">Confirmar cita</button>
        <a href="{{ $esPaciente ? route('dashboard') : route('citas.index') }}" class="btn btn-ghost btn-bloque">Cancelar</a>
    </aside>
</form>
@endsection

@push('scripts')
<script src="{{ asset('js/cita.js') }}"></script>
@endpush
