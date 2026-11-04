@extends('compartido.layouts.app')
@section('titulo', 'Consulta ' . $consulta->cita->codigo)
@section('migas', 'Mi consulta / Consulta ' . $consulta->cita->codigo)

@section('contenido')
@php($cita = $consulta->cita)
<div class="cabecera"><div><h1>Consulta de {{ $cita->paciente->nombre_completo }}</h1>
    <p class="sub">{{ $consulta->fecha_hora->format('d/m/Y H:i') }} · Cita {{ $cita->codigo }} · {{ $cita->especialidad->nombre }} · {{ $cita->medico->nombre_completo }}</p></div>
    <div class="botones">
        <a href="{{ route('historial.show', $cita->paciente) }}" class="btn btn-sec">Historial clínico</a>
        <a href="{{ route('agenda', ['fecha' => $cita->fecha->toDateString()]) }}" class="btn btn-ghost">Agenda</a>
    </div>
</div>
<div class="grid-2">
    <div class="card">
        <h2>Atención</h2>
        <dl class="datos datos-ancho">
            <dt>Motivo</dt><dd>{{ $consulta->motivo_consulta }}</dd>
            <dt>Signos vitales</dt><dd>Peso {{ $consulta->peso_kg ?? '—' }} kg · Talla {{ $consulta->talla_cm ?? '—' }} cm{{ $consulta->imc ? ' · IMC ' . $consulta->imc : '' }}<br>
                PA {{ $consulta->presion_arterial ?? '—' }} · T° {{ $consulta->temperatura_c ?? '—' }} °C · FC {{ $consulta->frecuencia_cardiaca ?? '—' }} lpm</dd>
            <dt>Examen físico</dt><dd>{{ $consulta->examen_fisico ?? '—' }}</dd>
            <dt>Diagnóstico</dt><dd><strong>{{ $consulta->diagnostico }}</strong></dd>
            <dt>Observaciones</dt><dd>{{ $consulta->observaciones ?? '—' }}</dd>
        </dl>
    </div>
    <div class="card">
        <h2>Tratamiento (CU17)</h2>
        <table class="tabla">
            <thead><tr><th>Medicamento</th><th>Dosis</th><th>Frecuencia</th><th>Duración</th><th></th></tr></thead>
            <tbody>
            @forelse($consulta->tratamientos as $t)
                <tr><td><strong>{{ $t->medicamento->nombre }}</strong><br><small class="sub">{{ $t->medicamento->presentacion }}{{ $t->indicaciones ? ' · ' . $t->indicaciones : '' }}</small></td>
                    <td>{{ $t->dosis }}</td><td>{{ $t->frecuencia }}</td><td>{{ $t->duracion }}</td>
                    <td><form method="POST" action="{{ route('tratamientos.destroy', $t) }}" data-confirmar="¿Quitar {{ $t->medicamento->nombre }} del tratamiento?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm" title="Quitar">✕</button></form></td></tr>
            @empty
                <tr><td colspan="5" class="vacio">Sin medicamentos indicados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="card">
    <h2>Agregar medicamento</h2>
    @include('P5_AtencionMedica.CU17_RegistrarTratamiento._agregar')
</div>
<div class="card">
    <h2>Estudios solicitados (CU18)</h2>
    <table class="tabla">
        <thead><tr><th>Estudio</th><th>Solicitado</th><th>Estado</th><th>Resultado</th><th></th></tr></thead>
        <tbody>
        @forelse($consulta->estudios as $e)
            <tr>
                <td><strong>{{ $e->catalogo->nombre }}</strong><br><small class="sub">{{ $e->indicaciones ?? ucfirst(strtolower($e->catalogo->categoria)) }}</small></td>
                <td>{{ $e->fecha_solicitud->format('d/m/Y') }}</td>
                <td><span class="chip {{ $e->tieneResultado() ? 'chip-atendida' : 'chip-programada' }}">{{ ucfirst(strtolower($e->estado)) }}</span></td>
                <td>{{ $e->resultado ? \Illuminate\Support\Str::limit($e->resultado, 70) : '—' }}
                    @if($e->archivo_resultado)<br><a href="{{ route('estudios.archivo', $e) }}">Descargar archivo</a>@endif</td>
                <td><a href="{{ route('estudios.resultado', $e) }}" class="btn btn-sm {{ $e->tieneResultado() ? 'btn-ghost' : 'btn-primario' }}">{{ $e->tieneResultado() ? 'Editar resultado' : 'Registrar resultado' }}</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="vacio">No se solicitaron estudios.</td></tr>
        @endforelse
        </tbody>
    </table>
    @include('P5_AtencionMedica.CU18_GestionarEstudiosMedicos._solicitar')
</div>
@endsection
