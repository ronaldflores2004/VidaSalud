@extends('compartido.layouts.app')
@section('titulo', 'Horarios')
@section('migas', 'Atención / Médicos / ' . $medico->codigo . ' / Horarios')

@section('contenido')
<div class="cabecera">
    <div><h1>Horarios de atención</h1><p class="sub">{{ $medico->nombre_completo }} · {{ $medico->especialidades->pluck('nombre')->join(', ') }}</p></div>
    <a href="{{ route('medicos.show', $medico) }}" class="btn btn-ghost">Volver al médico</a>
</div>
<div class="grid-2 grid-form">
    <form class="card form" method="POST" action="{{ route('horarios.store', $medico) }}" data-validar novalidate>
        @csrf
        <h2>Nuevo bloque</h2>
        <label class="campo"><span>Día *</span>
            <select name="dia_semana" required>
                <option value="">Seleccione…</option>
                @foreach($dias as $n => $d)<option value="{{ $n }}" @selected(old('dia_semana') == $n)>{{ $d }}</option>@endforeach
            </select></label>
        <div class="grid-2">
            <label class="campo"><span>Hora inicial *</span><input type="time" name="hora_inicio" value="{{ old('hora_inicio', '08:00') }}" required></label>
            <label class="campo"><span>Hora final *</span><input type="time" name="hora_fin" value="{{ old('hora_fin', '12:00') }}" required data-despues-de="hora_inicio"></label>
        </div>
        <p class="ayuda">Cada ficha dura <strong>15 minutos</strong> por paciente; el sistema calcula los cupos automáticamente.</p>
        <div class="form-pie"><button class="btn btn-primario">Agregar horario</button></div>
    </form>

    <div class="card">
        <h2>Semana</h2>
        <table class="tabla">
            <thead><tr><th>Día</th><th>Horario</th><th>Ficha</th><th>Cupos</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @forelse($medico->horarios as $h)
                @php($cupos = intdiv((strtotime($h->hora_fin) - strtotime($h->hora_inicio)) / 60, \App\Models\P2_GestionMedica\Horario::duracionFicha()))
                <tr class="{{ $h->activo ? '' : 'apagado' }}">
                    <td><strong>{{ $h->nombre_dia }}</strong></td>
                    <td class="mono">{{ substr($h->hora_inicio,0,5) }} – {{ substr($h->hora_fin,0,5) }}</td>
                    <td>{{ \App\Models\P2_GestionMedica\Horario::duracionFicha() }} min</td>
                    <td>{{ $cupos }}</td>
                    <td>@include('compartido.partials.estado', ['activo' => $h->activo])</td>
                    <td class="acciones">
                        <form method="POST" action="{{ route('horarios.estado', [$medico, $h]) }}">@csrf @method('PATCH')<button class="btn btn-ghost btn-sm">{{ $h->activo ? 'Desactivar' : 'Activar' }}</button></form>
                        <form method="POST" action="{{ route('horarios.destroy', [$medico, $h]) }}" data-confirmar="¿Eliminar el bloque del {{ $h->nombre_dia }}?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm">Quitar</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="vacio">Sin horarios. Agregue el primer bloque.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
