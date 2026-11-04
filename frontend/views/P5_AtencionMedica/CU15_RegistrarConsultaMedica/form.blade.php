@extends('compartido.layouts.app')
@section('titulo', 'Atender ' . $cita->codigo)
@section('migas', 'Mi consulta / Agenda / Atender')

@section('contenido')
<div class="cabecera"><div><h1>Registrar consulta médica</h1>
    <p class="sub">Cita {{ $cita->codigo }} · {{ $cita->fecha->format('d/m/Y') }} {{ $cita->horario }} · {{ $cita->especialidad->nombre }}</p></div>
    @include('compartido.partials.estado-cita', ['estado' => $cita->estado])
</div>

<form method="POST" action="{{ route('consultas.store', $cita) }}" class="consulta-layout" data-validar novalidate>
    @csrf
    <div>
        <section class="card form">
            <h2>Atención</h2>
            <label class="campo"><span>Motivo de consulta *</span>
                <input name="motivo_consulta" required minlength="3" maxlength="255" value="{{ old('motivo_consulta', $cita->motivo) }}"></label>
            <h2>Signos vitales</h2>
            <div class="signos">
                <label class="campo"><span>Peso (kg)</span><input type="number" step="0.1" min="0.5" max="400" name="peso_kg" value="{{ old('peso_kg') }}"></label>
                <label class="campo"><span>Talla (cm)</span><input type="number" step="0.1" min="30" max="250" name="talla_cm" value="{{ old('talla_cm') }}"></label>
                <label class="campo"><span>Presión arterial</span><input name="presion_arterial" pattern="\d{2,3}/\d{2,3}" placeholder="120/80" value="{{ old('presion_arterial') }}" data-msg="Ej. 120/80"></label>
                <label class="campo"><span>Temperatura (°C)</span><input type="number" step="0.1" min="30" max="45" name="temperatura_c" value="{{ old('temperatura_c') }}" data-msg="Entre 30 y 45 °C."></label>
                <label class="campo"><span>Frec. cardiaca</span><input type="number" min="30" max="220" name="frecuencia_cardiaca" value="{{ old('frecuencia_cardiaca') }}"></label>
            </div>
            <label class="campo"><span>Examen físico</span><textarea name="examen_fisico" rows="3" maxlength="2000">{{ old('examen_fisico') }}</textarea></label>
            <label class="campo"><span>Diagnóstico *</span><textarea name="diagnostico" rows="3" required minlength="5" maxlength="2000" data-msg="Escriba el diagnóstico (mínimo 5 caracteres).">{{ old('diagnostico') }}</textarea></label>
            <label class="campo"><span>Observaciones</span><textarea name="observaciones" rows="2" maxlength="2000">{{ old('observaciones') }}</textarea></label>
        </section>

        <section class="card">
            <h2>Tratamiento (CU17)</h2>
            <p class="ayuda">Opcional. Deje en blanco las filas que no use.</p>
            @include('P5_AtencionMedica.CU17_RegistrarTratamiento._filas')
        </section>

        <section class="card">
            <h2>Estudios a solicitar (CU18)</h2>
            <p class="ayuda">Puede marcar uno o varios estudios.</p>
            @foreach($catalogo->groupBy('categoria') as $categoria => $items)
                <p class="sub"><strong>{{ ucfirst(strtolower($categoria)) }}</strong></p>
                @foreach($items as $e)
                <div class="fila-trat" style="grid-template-columns: 1.4fr 2fr">
                    <label class="check-card"><input type="checkbox" name="estudios[{{ $e->id }}][sel]" value="1" @checked(old("estudios.{$e->id}.sel"))> {{ $e->nombre }}</label>
                    <input name="estudios[{{ $e->id }}][indicaciones]" maxlength="255" placeholder="Indicaciones (opcional)" value="{{ old("estudios.{$e->id}.indicaciones") }}">
                </div>
                @endforeach
            @endforeach
        </section>
        <div class="form-pie">
            <a href="{{ route('agenda', ['fecha' => $cita->fecha->toDateString()]) }}" class="btn btn-ghost">Volver a la agenda</a>
            <button class="btn btn-primario" type="submit">Guardar consulta</button>
        </div>
    </div>

    {{-- <<include>> CU16: datos del historial clínico a la vista del médico --}}
    <aside class="card resumen">
        <h2>{{ $paciente->nombre_completo }}</h2>
        <p class="sub">{{ $paciente->codigo }} · {{ $paciente->edad }} años · {{ $paciente->sexo === 'F' ? 'Femenino' : ($paciente->sexo === 'M' ? 'Masculino' : 'Otro') }} · Sangre {{ $paciente->tipo_sangre ?? '—' }}</p>
        @if($historia->alergias)<div class="alerta-clinica"><strong>Alergias:</strong> {{ $historia->alergias }}</div>@endif
        <dl class="datos">
            <dt>Historial</dt><dd class="mono">{{ $historia->numero }}</dd>
            <dt>Crónicas</dt><dd>{{ $historia->enfermedades_cronicas ?? '—' }}</dd>
            <dt>Medicación</dt><dd>{{ $historia->medicacion_habitual ?? '—' }}</dd>
        </dl>
        <h3 style="margin:1rem 0 .4rem;font-size:.95rem">Últimas consultas</h3>
        @forelse($anteriores as $a)
            <p class="ayuda"><strong>{{ $a->fecha_hora->format('d/m/Y') }}</strong> · {{ $a->cita->medico->nombre_completo }}<br>{{ \Illuminate\Support\Str::limit($a->diagnostico, 90) }}</p>
        @empty
            <p class="ayuda">Primera consulta registrada en el sistema.</p>
        @endforelse
        <a href="{{ route('historial.show', $paciente) }}" class="btn btn-ghost btn-bloque" target="_blank">Ver historial completo (CU16)</a>
    </aside>
</form>
@endsection

@push('scripts')
<script src="{{ url('assets/js/P5_AtencionMedica/CU17_RegistrarTratamiento/tratamiento.js') }}"></script>
@endpush
