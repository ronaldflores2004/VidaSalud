@extends('compartido.layouts.app')
@section('titulo', 'Mi historial')
@section('migas', 'Mis fichas / Mi historial')

@section('contenido')
<div class="cabecera"><div><h1>Mi historial clínico</h1>
    <p class="sub">{{ $paciente->nombre_completo }} · Historial {{ $historia->numero }} · Sangre {{ $paciente->tipo_sangre ?? '—' }}</p></div></div>

@if($avisos->isNotEmpty())
<div class="card">
    <h2>Avisos</h2>
    @foreach($avisos as $a)
        <div class="aviso {{ $a->leida_en ? '' : 'nuevo' }}"><strong>{{ $a->tipo_texto }}</strong> {{ $a->mensaje }}<small>{{ $a->programada_para->format('d/m/Y H:i') }}</small></div>
    @endforeach
</div>
@endif

<div class="grid-2">
    <div class="card">
        <h2>Mis datos clínicos</h2>
        <dl class="datos datos-ancho">
            <dt>Alergias</dt><dd>{{ $historia->alergias ?? 'Ninguna registrada' }}</dd>
            <dt>Enfermedades crónicas</dt><dd>{{ $historia->enfermedades_cronicas ?? '—' }}</dd>
            <dt>Medicación habitual</dt><dd>{{ $historia->medicacion_habitual ?? '—' }}</dd>
        </dl>
    </div>
    <div class="card">
        <h2>Resultados de estudios</h2>
        <table class="tabla">
            <thead><tr><th>Estudio</th><th>Estado</th><th>Resultado</th></tr></thead>
            <tbody>
            @forelse($estudios as $e)
                <tr><td><strong>{{ $e->catalogo->nombre }}</strong><br><small class="sub">Pedido el {{ $e->fecha_solicitud->format('d/m/Y') }}</small></td>
                    <td><span class="chip {{ $e->tieneResultado() ? 'chip-atendida' : 'chip-programada' }}">{{ $e->tieneResultado() ? 'Listo' : 'Pendiente' }}</span></td>
                    <td>@if($e->tieneResultado()){{ $e->resultado }}@if($e->archivo_resultado)<br><a href="{{ route('estudios.archivo', $e) }}">Descargar archivo</a>@endif @else <span class="sub">Aún no está disponible</span>@endif</td></tr>
            @empty
                <tr><td colspan="3" class="vacio">No tiene estudios solicitados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>Mis consultas y tratamientos</h2>
    <div class="linea-tiempo">
    @forelse($consultas as $c)
        <div class="evento">
            <h3>{{ $c->fecha_hora->format('d/m/Y') }} · {{ $c->cita->especialidad->nombre }}</h3>
            <p class="sub">{{ $c->cita->medico->nombre_completo }} · Motivo: {{ $c->motivo_consulta }}</p>
            <p><strong>Diagnóstico:</strong> {{ $c->diagnostico }}</p>
            @if($c->tratamientos->isNotEmpty())
                <table class="tabla">
                    <thead><tr><th>Medicamento</th><th>Dosis</th><th>Frecuencia</th><th>Duración</th><th>Indicaciones</th></tr></thead>
                    <tbody>@foreach($c->tratamientos as $t)<tr><td>{{ $t->medicamento->nombre }} <small class="sub">{{ $t->medicamento->presentacion }}</small></td><td>{{ $t->dosis }}</td><td>{{ $t->frecuencia }}</td><td>{{ $t->duracion }}</td><td>{{ $t->indicaciones ?? '—' }}</td></tr>@endforeach</tbody>
                </table>
            @endif
        </div>
    @empty
        <p class="vacio">Todavía no tiene consultas registradas.</p>
    @endforelse
    </div>
</div>
@endsection
