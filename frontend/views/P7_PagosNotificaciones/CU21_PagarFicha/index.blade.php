@extends('compartido.layouts.app')
@section('titulo', 'Pagos')
@section('migas', 'Atención / Pagos')

@section('contenido')
<div class="cabecera"><div><h1>Pagos de fichas</h1>
    <p class="sub">Pagos en línea (PayPal) y cobros en recepción. Las fichas pagadas que se cancelan quedan por reembolsar.</p></div></div>
<div class="kpis">
    <div class="kpi"><span>Cobrado hoy</span><strong>Bs {{ number_format($totalHoy, 2) }}</strong></div>
    <div class="kpi"><span>Por reembolsar</span><strong>{{ $porReembolsar }}</strong></div>
</div>
<form class="filtros" method="GET">
    <input type="date" name="fecha" value="{{ request('fecha') }}" aria-label="Fecha de pago">
    <select name="metodo"><option value="">Todos los métodos</option>
        @foreach(\App\Models\P7_PagosNotificaciones\Pago::METODOS as $k => $t)<option value="{{ $k }}" @selected(request('metodo') === $k)>{{ $t }}</option>@endforeach
    </select>
    <select name="estado"><option value="">Todos los estados</option>
        @foreach(\App\Models\P7_PagosNotificaciones\Pago::ESTADOS as $k => $t)<option value="{{ $k }}" @selected(request('estado') === $k)>{{ $t }}</option>@endforeach
    </select>
    <button class="btn btn-sec">Filtrar</button>
</form>
<div class="card">
    <table class="tabla">
        <thead><tr><th>Código</th><th>Fecha de pago</th><th>Ficha</th><th>Paciente</th><th>Monto</th><th>Método</th><th>Referencia</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse($pagos as $p)
            <tr>
                <td class="mono">{{ $p->codigo }}</td>
                <td>{{ $p->pagado_en->format('d/m/Y H:i') }}</td>
                <td><a href="{{ route('citas.show', $p->cita) }}">{{ $p->cita->codigo }}</a><br><small class="sub">{{ $p->cita->especialidad->nombre }}</small></td>
                <td>{{ $p->cita->paciente->nombre_completo }}</td>
                <td class="mono">Bs {{ number_format($p->monto, 2) }}@if($p->monto_usd)<br><small class="sub">USD {{ number_format($p->monto_usd, 2) }}</small>@endif</td>
                <td>{{ $p->metodo_texto }}</td>
                <td class="mono"><small>{{ $p->referencia ?? '—' }}</small></td>
                <td>{{ \App\Models\P7_PagosNotificaciones\Pago::ESTADOS[$p->estado] }}</td>
                <td class="acciones">
                    @if($p->estado === 'POR_REEMBOLSAR')
                        <form method="POST" action="{{ route('pagos.reembolsar', $p) }}" data-confirmar="¿Registrar la devolución de Bs {{ number_format($p->monto, 2) }} a {{ $p->cita->paciente->nombre_completo }}?">
                            @csrf @method('PATCH')<button class="btn btn-sec btn-sm">Reembolsar</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="vacio">No hay pagos con esos filtros.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $pagos->links('compartido.partials.paginacion') }}
</div>
@endsection
