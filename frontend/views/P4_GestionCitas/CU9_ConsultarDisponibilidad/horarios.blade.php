@extends('compartido.layouts.publico')
@section('titulo', 'Horarios disponibles')

@section('contenido')
<section class="pub-seccion">
    <h1>Horarios disponibles</h1>
    <p class="sub">Elija especialidad, médico y fecha. Las fichas en gris ya están ocupadas.</p>
    <div class="card" id="consulta-horarios" data-url-medicos="{{ url('api/especialidades') }}" data-url-bloques="{{ url('api/medicos') }}">
        <div class="grid-3">
            <label class="campo"><span>Especialidad</span>
                <select id="especialidad_id"><option value="">Seleccione…</option>
                    @foreach($especialidades as $e)<option value="{{ $e->id }}">{{ $e->nombre }}</option>@endforeach
                </select></label>
            <label class="campo"><span>Médico</span><select id="medico_id" disabled><option value="">Primero elija la especialidad</option></select></label>
            <label class="campo"><span>Fecha</span><input type="date" id="fecha" min="{{ today()->toDateString() }}"></label>
        </div>
        <p class="ayuda" id="dias-medico"></p>
        <div id="bloques"><p class="ayuda">Elija médico y fecha para ver las fichas.</p></div>
        <div class="form-pie">
            @auth
                @if(auth()->user()->tieneRol('Paciente'))<a href="{{ route('citas.create') }}" class="btn btn-primario">Reservar una ficha</a>@endif
            @else
                <span class="sub">Para reservar debe <a href="{{ route('login') }}">iniciar sesión</a> o <a href="{{ route('registro') }}">registrarse</a>.</span>
            @endauth
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ url('assets/js/P4_GestionCitas/CU9_ConsultarDisponibilidad/horarios-publicos.js') }}"></script>
@endpush
