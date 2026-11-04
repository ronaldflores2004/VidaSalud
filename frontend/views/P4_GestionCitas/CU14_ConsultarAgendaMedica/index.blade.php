@extends('compartido.layouts.app')
@section('titulo', 'Mi agenda')
@section('migas', 'Mi consulta / Agenda')

@section('contenido')
<div class="cabecera"><div><h1>Mi agenda</h1>
    <p class="sub">{{ $medico->nombre_completo }} · {{ ucfirst($fecha->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}</p></div>
    <form method="GET" class="botones">
        <a class="btn btn-ghost btn-sm" href="{{ route('agenda', ['fecha' => $fecha->copy()->subDay()->toDateString()]) }}">← Día anterior</a>
        <input type="date" name="fecha" value="{{ $fecha->toDateString() }}" onchange="this.form.submit()" style="width:auto">
        <a class="btn btn-ghost btn-sm" href="{{ route('agenda', ['fecha' => $fecha->copy()->addDay()->toDateString()]) }}">Día siguiente →</a>
        <a class="btn btn-sec btn-sm" href="{{ route('agenda') }}">Hoy</a>
    </form>
</div>
<div class="semana card">
    @for($i = 0; $i < 7; $i++)
        @php($d = $inicioSemana->copy()->addDays($i))
        <a href="{{ route('agenda', ['fecha' => $d->toDateString()]) }}" class="{{ $d->isSameDay($fecha) ? 'activo' : '' }}">
            <small>{{ $d->locale('es')->isoFormat('ddd') }}</small><strong>{{ $d->format('d') }}</strong>
            <span>{{ $semana[$d->toDateString()] ?? 0 }} cita(s)</span>
        </a>
    @endfor
</div>
<div class="card">
    <table class="tabla">
        <thead><tr><th>Hora</th><th>Paciente</th><th>Edad</th><th>Motivo</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse($citas as $c)
            <tr>
                <td class="mono">{{ $c->horario }}</td>
                <td><strong>{{ $c->paciente->nombre_completo }}</strong><br><small class="sub">{{ $c->paciente->codigo }} · {{ $c->especialidad->nombre }} · {{ ucfirst(strtolower($c->modalidad)) }}</small></td>
                <td>{{ $c->paciente->edad }} años</td>
                <td>{{ $c->motivo ?? '—' }}</td>
                <td>@include('compartido.partials.estado-cita', ['estado' => $c->estado])</td>
                <td class="acciones">
                    @if($c->consulta)
                        <a href="{{ route('consultas.show', $c->consulta) }}" class="btn btn-sec btn-sm">Ver consulta</a>
                    @elseif($c->estaVigente() && $c->fecha->isToday())
                        <a href="{{ route('consultas.create', $c) }}" class="btn btn-primario btn-sm">Atender</a>
                    @endif
                    @if($c->estado !== 'CANCELADA')
                        <a href="{{ route('historial.show', $c->paciente) }}" class="btn btn-ghost btn-sm">Historial</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="vacio">No tiene citas para este día.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
