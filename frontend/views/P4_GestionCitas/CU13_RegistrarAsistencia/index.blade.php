@extends('compartido.layouts.app')
@section('titulo', 'Asistencia de hoy')
@section('migas', 'Atención / Asistencia')

@section('contenido')
<div class="cabecera"><div><h1>Asistencia de hoy</h1>
    <p class="sub">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }} · Marque la llegada del paciente o su inasistencia.</p></div></div>
<div class="kpis">
    <div class="kpi"><span>Programadas</span><strong>{{ $resumen['PROGRAMADA'] ?? 0 }}</strong></div>
    <div class="kpi"><span>Llegaron (confirmadas)</span><strong>{{ $resumen['CONFIRMADA'] ?? 0 }}</strong></div>
    <div class="kpi"><span>Atendidas</span><strong>{{ $resumen['ATENDIDA'] ?? 0 }}</strong></div>
    <div class="kpi"><span>Inasistencias</span><strong>{{ $resumen['INASISTENCIA'] ?? 0 }}</strong></div>
</div>
<form class="filtros" method="GET">
    <select name="medico"><option value="">Todos los médicos</option>
        @foreach($medicos as $m)<option value="{{ $m->id }}" @selected(request('medico') == $m->id)>{{ $m->nombre_completo }}</option>@endforeach
    </select>
    <button class="btn btn-sec">Filtrar</button>
</form>
<div class="card">
    <table class="tabla">
        <thead><tr><th>Hora</th><th>Código</th><th>Paciente</th><th>Médico</th><th>Estado</th><th>Pago</th><th>Registrar</th></tr></thead>
        <tbody>
        @forelse($citas as $c)
            <tr>
                <td class="mono">{{ $c->horario }}</td>
                <td><a href="{{ route('citas.show', $c) }}">{{ $c->codigo }}</a></td>
                <td>{{ $c->paciente->nombre_completo }}</td>
                <td>{{ $c->medico->nombre_completo }}<br><small class="sub">{{ $c->especialidad->nombre }}</small></td>
                <td>@include('compartido.partials.estado-cita', ['estado' => $c->estado])</td>
                <td>
                    @if(! $c->pago && $c->estaVigente())
                        @include('P7_PagosNotificaciones.CU21_PagarFicha._cobrar', ['cita' => $c])
                    @else
                        @include('compartido.partials.estado-pago', ['cita' => $c])
                    @endif
                </td>
                <td class="acciones">
                    @if($c->estado === 'PROGRAMADA')
                        @if($c->pago)
                        <form method="POST" action="{{ route('asistencia.store', $c) }}">@csrf<input type="hidden" name="tipo" value="LLEGADA"><button class="btn btn-primario btn-sm">Llegó</button></form>
                        @endif
                        @if($c->inicio->lte(now()))
                        <form method="POST" action="{{ route('asistencia.store', $c) }}" data-confirmar="¿Registrar inasistencia de {{ $c->paciente->nombre_completo }}?">@csrf<input type="hidden" name="tipo" value="INASISTENCIA"><button class="btn btn-ghost btn-sm">No asistió</button></form>
                        @endif
                    @else
                        <span class="sub">—</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="vacio">No hay citas para hoy.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
