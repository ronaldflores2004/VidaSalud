@extends('compartido.layouts.app')
@section('titulo', 'Reportes')
@section('migas', 'Administración / Reportes')

@section('contenido')
<div class="cabecera"><div><h1>Reportes</h1><p class="sub">Elija el reporte y los filtros; revise la vista previa y expórtelo a PDF o Excel.</p></div></div>
<form class="card reporte-filtros" method="GET" action="{{ route('reportes.index') }}">
    <label class="campo"><span>Reporte</span>
        <select name="tipo">@foreach($tipos as $k => $v)<option value="{{ $k }}" @selected($f['tipo'] === $k)>{{ $v }}</option>@endforeach</select></label>
    <label class="campo"><span>Desde</span><input type="date" name="desde" value="{{ $f['desde'] }}" required></label>
    <label class="campo"><span>Hasta</span><input type="date" name="hasta" value="{{ $f['hasta'] }}" required></label>
    <label class="campo"><span>Médico</span>
        <select name="medico_id"><option value="">Todos</option>@foreach($medicos as $m)<option value="{{ $m->id }}" @selected(($f['medico_id'] ?? null) == $m->id)>{{ $m->nombre_completo }}</option>@endforeach</select></label>
    <label class="campo"><span>Especialidad</span>
        <select name="especialidad_id"><option value="">Todas</option>@foreach($especialidades as $e)<option value="{{ $e->id }}" @selected(($f['especialidad_id'] ?? null) == $e->id)>{{ $e->nombre }}</option>@endforeach</select></label>
    <label class="campo"><span>Estado (citas)</span>
        <select name="estado"><option value="">Todos</option>@foreach($estados as $e)<option value="{{ $e }}" @selected(($f['estado'] ?? null) === $e)>{{ ucfirst(strtolower($e)) }}</option>@endforeach</select></label>
    <div class="botones" style="grid-column: 1 / -1">
        <button class="btn btn-sec">Ver reporte</button>
        <a class="btn btn-primario" href="{{ route('reportes.exportar', ['formato' => 'pdf'] + $f) }}">Exportar PDF</a>
        <a class="btn btn-primario" href="{{ route('reportes.exportar', ['formato' => 'excel'] + $f) }}">Exportar Excel</a>
    </div>
</form>

<div class="card">
    <div class="card-cab"><h2>{{ $reporte['titulo'] }}</h2><span class="sub">{{ $reporte['filtros'] }}</span></div>
    <div class="kpis">
        @foreach(array_slice($reporte['resumen'], 0, 4) as [$etiqueta, $valor])
            <div class="kpi"><span>{{ $etiqueta }}</span><strong>{{ $valor }}</strong></div>
        @endforeach
    </div>
    @if(count($reporte['resumen']) > 4)
        <p class="ayuda">@foreach(array_slice($reporte['resumen'], 4) as [$etiqueta, $valor]){{ $etiqueta }}: <strong>{{ $valor }}</strong> · @endforeach</p>
    @endif
    <table class="tabla">
        <thead><tr>@foreach($reporte['columnas'] as [$titulo])<th>{{ $titulo }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($reporte['filas'] as $fila)
            <tr>@foreach($fila as $valor)<td>{{ $valor }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($reporte['columnas']) }}" class="vacio">No hay datos para los filtros seleccionados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
