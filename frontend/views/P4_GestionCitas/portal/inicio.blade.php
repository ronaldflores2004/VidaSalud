@extends('compartido.layouts.publico')
@section('titulo', 'Inicio')

@section('contenido')
<section class="hero">
    <div class="hero-txt">
        <h1>Reserve su ficha médica sin hacer fila</h1>
        <p>Vea los horarios disponibles de nuestros médicos, regístrese con sus datos y reserve una ficha de 15 minutos desde su celular o computadora.</p>
        <div class="botones">
            <a href="{{ route('portal.horarios') }}" class="btn btn-primario">Ver horarios disponibles</a>
            @guest<a href="{{ route('registro') }}" class="btn btn-sec">Crear mi cuenta de paciente</a>@endguest
        </div>
    </div>
    <ol class="hero-pasos">
        <li><strong>Regístrese</strong><span>con su CI y datos de contacto</span></li>
        <li><strong>Elija especialidad y médico</strong><span>y vea sus fichas libres</span></li>
        <li><strong>Reserve su ficha</strong><span>y preséntese 10 minutos antes</span></li>
    </ol>
</section>

<section class="pub-seccion" id="especialidades">
    <h2>Especialidades</h2>
    <div class="esp-grid">
        @foreach($especialidades as $e)
            <div class="esp-card"><strong>{{ $e->nombre }}</strong><span>{{ $e->descripcion }}</span><small>{{ $e->medicos_count }} médico(s)</small></div>
        @endforeach
    </div>
</section>

<section class="pub-seccion" id="medicos">
    <h2>Nuestros médicos</h2>
    <div class="med-grid">
        @foreach($medicos as $m)
            <div class="card med-card">
                <div class="avatar avatar-claro">{{ mb_substr($m->nombres, 0, 1) }}{{ mb_substr($m->apellidos, 0, 1) }}</div>
                <div>
                    <strong>{{ $m->nombre_completo }}</strong>
                    <div>@foreach($m->especialidades as $e)<span class="tag">{{ $e->nombre }}</span>@endforeach</div>
                    <small class="sub">@forelse($m->horarios as $h){{ $h->nombre_dia }} {{ substr($h->hora_inicio,0,5) }}–{{ substr($h->hora_fin,0,5) }}@if(! $loop->last) · @endif @empty Sin horario publicado @endforelse</small>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection
