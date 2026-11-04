@extends('compartido.layouts.app')
@section('titulo', 'Historial ' . $historia->numero)
@section('migas', 'Mi consulta / Mis pacientes / ' . $historia->numero)

@section('contenido')
<div class="cabecera"><div><h1>Historial clínico {{ $historia->numero }}</h1>
    <p class="sub">{{ $paciente->nombre_completo }} · {{ $paciente->codigo }} · CI {{ $paciente->ci ?? '—' }} · {{ $paciente->edad }} años · Sangre {{ $paciente->tipo_sangre ?? '—' }}</p></div>
    <a href="{{ route('historial.index') }}" class="btn btn-ghost">Mis pacientes</a>
</div>
<div class="grid-form grid-2">
    <form class="card form" method="POST" action="{{ route('historial.update', $paciente) }}">
        @csrf @method('PUT')
        <h2>Antecedentes</h2>
        @if($historia->alergias)<div class="alerta-clinica"><strong>Alergias:</strong> {{ $historia->alergias }}</div>@endif
        <label class="campo"><span>Alergias</span><input name="alergias" maxlength="255" value="{{ old('alergias', $historia->alergias) }}"></label>
        <label class="campo"><span>Enfermedades crónicas</span><input name="enfermedades_cronicas" maxlength="255" value="{{ old('enfermedades_cronicas', $historia->enfermedades_cronicas) }}"></label>
        <label class="campo"><span>Medicación habitual</span><input name="medicacion_habitual" maxlength="255" value="{{ old('medicacion_habitual', $historia->medicacion_habitual) }}"></label>
        <label class="campo"><span>Antecedentes personales</span><textarea name="antecedentes_personales" rows="3" maxlength="2000">{{ old('antecedentes_personales', $historia->antecedentes_personales) }}</textarea></label>
        <label class="campo"><span>Antecedentes familiares</span><textarea name="antecedentes_familiares" rows="3" maxlength="2000">{{ old('antecedentes_familiares', $historia->antecedentes_familiares) }}</textarea></label>
        <div class="form-pie"><button class="btn btn-primario">Guardar antecedentes</button></div>
        @if($paciente->contactos->isNotEmpty())
            <p class="ayuda">Emergencia: @foreach($paciente->contactos as $c){{ $c->parentesco }} · {{ $c->nombre }} · {{ $c->telefono }}@endforeach</p>
        @endif
    </form>
    <div class="card">
        <h2>Consultas ({{ $consultas->count() }})</h2>
        <div class="linea-tiempo">
        @forelse($consultas as $c)
            <div class="evento">
                <h3>{{ $c->fecha_hora->format('d/m/Y') }} · {{ $c->cita->especialidad->nombre }}</h3>
                <p class="sub">{{ $c->cita->medico->nombre_completo }} · Motivo: {{ $c->motivo_consulta }}</p>
                <p><strong>Diagnóstico:</strong> {{ $c->diagnostico }}</p>
                @if($c->tratamientos->isNotEmpty())
                    <p>@foreach($c->tratamientos as $t)<span class="tag">{{ $t->medicamento->nombre }} {{ $t->dosis }} {{ $t->frecuencia }} · {{ $t->duracion }}</span>@endforeach</p>
                @endif
                @foreach($c->estudios as $e)
                    <p class="ayuda">Estudio: <strong>{{ $e->catalogo->nombre }}</strong> — {{ ucfirst(strtolower($e->estado)) }}{{ $e->resultado ? ': ' . \Illuminate\Support\Str::limit($e->resultado, 80) : '' }}</p>
                @endforeach
                @if($c->cita->medico_id === auth()->user()->medico?->id)<a href="{{ route('consultas.show', $c) }}" class="enlace">Abrir consulta</a>@endif
            </div>
        @empty
            <p class="vacio">El paciente todavía no tiene consultas registradas.</p>
        @endforelse
        </div>
    </div>
</div>
@endsection
