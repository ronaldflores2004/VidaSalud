@extends('layouts.app')
@section('titulo', $medico->nombre_completo)
@section('migas', 'Atención / Médicos / ' . $medico->codigo)

@section('contenido')
<div class="cabecera">
    <div><h1>{{ $medico->nombre_completo }}</h1>
        <p class="sub"><span class="mono">{{ $medico->codigo }}</span> · Reg. {{ $medico->registro_profesional }} · @include('partials.estado', ['activo' => $medico->activo])</p></div>
    @if(auth()->user()->tieneRol('Administrador'))
    <div class="botones">
        <a href="{{ route('horarios.index', $medico) }}" class="btn btn-sec">Horarios</a>
        <a href="{{ route('medicos.edit', $medico) }}" class="btn btn-sec">Editar</a>
        <form method="POST" action="{{ route('medicos.estado', $medico) }}" data-confirmar="{{ $medico->activo ? 'Un médico inactivo no podrá recibir nuevas citas. ¿Continuar?' : '¿Activar al médico?' }}">
            @csrf @method('PATCH')<button class="btn {{ $medico->activo ? 'btn-peligro' : 'btn-primario' }}">{{ $medico->activo ? 'Desactivar' : 'Activar' }}</button>
        </form>
    </div>
    @endif
</div>
<div class="grid-2">
    <div class="card">
        <h2>Datos</h2>
        <dl class="datos">
            <dt>Especialidades</dt><dd>@foreach($medico->especialidades as $e)<span class="tag">{{ $e->nombre }}</span>@endforeach</dd>
            <dt>Teléfono</dt><dd>{{ $medico->telefono }}</dd>
            <dt>Correo</dt><dd>{{ $medico->email ?? '—' }}</dd>
            <dt>Cuenta de acceso</dt><dd>{{ $medico->usuario?->username ?? 'Sin cuenta' }}</dd>
        </dl>
    </div>
    <div class="card">
        <h2>Horario de atención</h2>
        @forelse($medico->horarios as $h)
            <div class="fila-horario {{ $h->activo ? '' : 'apagado' }}"><strong>{{ $h->nombre_dia }}</strong><span class="mono">{{ substr($h->hora_inicio,0,5) }} – {{ substr($h->hora_fin,0,5) }}</span><small>citas de {{ $h->duracion_cita }} min</small></div>
        @empty
            <p class="vacio">Aún no tiene horarios registrados.</p>
        @endforelse
    </div>
</div>
@endsection
