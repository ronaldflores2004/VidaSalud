@extends('compartido.layouts.app')
@section('titulo', 'Estudios')
@section('migas', 'Mi consulta / Estudios')

@section('contenido')
<div class="cabecera"><div><h1>Estudios solicitados</h1><p class="sub">Primero se muestran los que aún no tienen resultado.</p></div></div>
<form class="filtros" method="GET">
    <select name="estado"><option value="">Todos</option>
        @foreach(['SOLICITADO', 'REALIZADO', 'ENTREGADO'] as $e)<option value="{{ $e }}" @selected(request('estado') === $e)>{{ ucfirst(strtolower($e)) }}</option>@endforeach
    </select>
    <button class="btn btn-sec">Filtrar</button>
</form>
<div class="card">
    <table class="tabla">
        <thead><tr><th>Solicitado</th><th>Paciente</th><th>Estudio</th><th>Estado</th><th>Resultado</th><th></th></tr></thead>
        <tbody>
        @forelse($estudios as $e)
            <tr>
                <td>{{ $e->fecha_solicitud->format('d/m/Y') }}</td>
                <td>{{ $e->consulta->cita->paciente->nombre_completo }}</td>
                <td><strong>{{ $e->catalogo->nombre }}</strong><br><small class="sub">{{ $e->indicaciones }}</small></td>
                <td><span class="chip {{ $e->tieneResultado() ? 'chip-atendida' : 'chip-programada' }}">{{ ucfirst(strtolower($e->estado)) }}</span></td>
                <td>{{ $e->fecha_resultado?->format('d/m/Y') ?? '—' }}</td>
                <td class="acciones"><a href="{{ route('estudios.resultado', $e) }}" class="btn btn-sm {{ $e->tieneResultado() ? 'btn-ghost' : 'btn-primario' }}">{{ $e->tieneResultado() ? 'Ver / editar' : 'Registrar resultado' }}</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="vacio">No hay estudios.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $estudios->links('compartido.partials.paginacion') }}
</div>
@endsection
