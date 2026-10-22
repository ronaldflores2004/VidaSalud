<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Inicio') · Clínica Vida Salud</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php($u = auth()->user())
<div class="shell">
    {{-- Menú lateral: las opciones dependen del rol (RF01) --}}
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-mark">+</span>
            <span><strong>Vida Salud</strong><small>Gestión de citas</small></span>
        </a>
        <nav class="menu">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'activo' : '' }}">Inicio</a>

            @if($u->tieneRol('Administrador', 'Recepcionista'))
                <span class="menu-grupo">Atención</span>
                <a href="{{ route('citas.create') }}" class="{{ request()->routeIs('citas.create') ? 'activo' : '' }}">Programar cita</a>
                <a href="{{ route('citas.index') }}" class="{{ request()->routeIs('citas.index', 'citas.show') ? 'activo' : '' }}">Citas</a>
                <a href="{{ route('pacientes.index') }}" class="{{ request()->routeIs('pacientes.*') ? 'activo' : '' }}">Pacientes</a>
                <a href="{{ route('medicos.index') }}" class="{{ request()->routeIs('medicos.*', 'horarios.*') ? 'activo' : '' }}">Médicos</a>
            @endif

            @if($u->tieneRol('Medico'))
                <span class="menu-grupo">Mi consulta</span>
                <a href="{{ route('citas.index') }}" class="{{ request()->routeIs('citas.*') ? 'activo' : '' }}">Mi agenda</a>
            @endif

            @if($u->tieneRol('Paciente'))
                <span class="menu-grupo">Mis fichas</span>
                <a href="{{ route('citas.create') }}" class="{{ request()->routeIs('citas.create') ? 'activo' : '' }}">Reservar ficha</a>
                <a href="{{ route('citas.index') }}" class="{{ request()->routeIs('citas.index', 'citas.show') ? 'activo' : '' }}">Mis citas</a>
                <a href="{{ route('portal.horarios') }}">Horarios de médicos</a>
            @endif

            @if($u->tieneRol('Administrador'))
                <span class="menu-grupo">Administración</span>
                <a href="{{ route('especialidades.index') }}" class="{{ request()->routeIs('especialidades.*') ? 'activo' : '' }}">Especialidades</a>
                <a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios.*') ? 'activo' : '' }}">Usuarios</a>
            @endif
        </nav>
        <div class="sidebar-pie">
            <div class="avatar">{{ mb_substr($u->name, 0, 1) }}</div>
            <div class="quien"><strong>{{ $u->name }}</strong><small>{{ $u->rol->nombre === 'Medico' ? 'Médico' : $u->rol->nombre }}</small></div>
        </div>
    </aside>

    <main class="contenido">
        <header class="topbar">
            <button class="btn-menu" type="button" data-toggle-menu aria-label="Menú">☰</button>
            <div class="migas">@yield('migas')</div>
            <form method="POST" action="{{ route('logout') }}" data-confirmar="¿Desea cerrar la sesión?">
                @csrf
                <button class="btn btn-ghost btn-sm" type="submit">Cerrar sesión</button>
            </form>
        </header>

        <section class="pagina">
            @include('partials.alertas')
            @yield('contenido')
        </section>
    </main>
</div>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
