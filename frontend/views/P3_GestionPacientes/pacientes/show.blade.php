@extends('compartido.layouts.app')
@section('titulo', $paciente->nombre_completo)
@section('migas', 'Atención / Pacientes / ' . $paciente->codigo)

@section('contenido')
<div class="cabecera">
    <div><h1>{{ $paciente->nombre_completo }}</h1>
        <p class="sub"><span class="mono">{{ $paciente->codigo }}</span> · CI {{ $paciente->ci ?? '—' }} · {{ $paciente->edad }} años · @include('compartido.partials.estado', ['activo' => $paciente->activo])</p></div>
    <div class="botones">
        @if($paciente->activo)<a href="{{ route('citas.create', ['paciente' => $paciente->id]) }}" class="btn btn-primario">+ Programar cita</a>@endif
        <a href="{{ route('pacientes.edit', $paciente) }}" class="btn btn-sec">Editar</a>
        <form method="POST" action="{{ route('pacientes.estado', $paciente) }}" data-confirmar="{{ $paciente->activo ? 'El paciente se desactivará, pero su historial se conserva. ¿Continuar?' : '¿Activar al paciente?' }}">
            @csrf @method('PATCH')<button class="btn btn-ghost">{{ $paciente->activo ? 'Desactivar' : 'Activar' }}</button>
        </form>
    </div>
</div>
<div class="grid-3">
    <div class="card"><h2>Contacto</h2><dl class="datos">
        <dt>Teléfono</dt><dd>{{ $paciente->telefono }}</dd>
        <dt>Correo</dt><dd>{{ $paciente->email ?? '—' }}</dd>
        <dt>Dirección</dt><dd>{{ $paciente->direccion ?? '—' }}</dd></dl></div>
    <div class="card"><h2>Información médica</h2><dl class="datos">
        <dt>Nacimiento</dt><dd>{{ $paciente->fecha_nacimiento->format('d/m/Y') }}</dd>
        <dt>Tipo de sangre</dt><dd>{{ $paciente->tipo_sangre ?? '—' }}</dd>
        <dt>Alergias</dt><dd>{{ $paciente->alergias ?? 'Ninguna registrada' }}</dd></dl></div>
    <div class="card"><h2>Emergencia</h2><dl class="datos">
        <dt>Contacto</dt><dd>{{ $paciente->contacto_emergencia_nombre ?? '—' }}</dd>
        <dt>Parentesco</dt><dd>{{ $paciente->contacto_emergencia_parentesco ?? '—' }}</dd>
        <dt>Teléfono</dt><dd>{{ $paciente->contacto_emergencia_telefono ?? '—' }}</dd></dl></div>
</div>
<div class="card">
    <div class="card-cab"><h2>Citas del paciente</h2><span class="sub">{{ $paciente->citas->count() }} registradas</span></div>
    <table class="tabla">
        <thead><tr><th>Código</th><th>Fecha</th><th>Horario</th><th>Médico</th><th>Especialidad</th><th>Estado</th></tr></thead>
        <tbody>
        @forelse($paciente->citas as $c)
            <tr>
                <td><a href="{{ route('citas.show', $c) }}">{{ $c->codigo }}</a></td>
                <td>{{ $c->fecha->format('d/m/Y') }}</td>
                <td class="mono">{{ $c->horario }}</td>
                <td>{{ $c->medico->nombre_completo }}</td>
                <td>{{ $c->especialidad->nombre }}</td>
                <td>@include('compartido.partials.estado-cita', ['estado' => $c->estado])</td>
            </tr>
        @empty
            <tr><td colspan="6" class="vacio">El paciente aún no tiene citas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
