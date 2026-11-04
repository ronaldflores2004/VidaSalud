@extends('compartido.layouts.app')
@section('titulo', 'Mis pacientes')
@section('migas', 'Mi consulta / Mis pacientes')

@section('contenido')
<div class="cabecera"><div><h1>Mis pacientes</h1><p class="sub">Solo se listan los pacientes que tienen o tuvieron una cita con usted.</p></div></div>
<form class="filtros" method="GET">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="CI, nombre, apellido o código">
    <button class="btn btn-sec">Buscar</button>
</form>
<div class="card">
    <table class="tabla">
        <thead><tr><th>Código</th><th>Paciente</th><th>Edad</th><th>Historial</th><th>Alergias</th><th>Atenciones</th><th></th></tr></thead>
        <tbody>
        @forelse($pacientes as $p)
            <tr>
                <td class="mono">{{ $p->codigo }}</td>
                <td><strong>{{ $p->apellidos }}, {{ $p->nombres }}</strong></td>
                <td>{{ $p->edad }} años</td>
                <td class="mono">{{ $p->historia?->numero ?? '—' }}</td>
                <td>{{ $p->historia?->alergias ?? '—' }}</td>
                <td>{{ $p->atenciones }}</td>
                <td><a href="{{ route('historial.show', $p) }}" class="btn btn-ghost btn-sm">Ver historial</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="vacio">Aún no tiene pacientes.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $pacientes->links('compartido.partials.paginacion') }}
</div>
@endsection
