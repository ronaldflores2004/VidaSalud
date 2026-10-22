<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Inicio') · Clínica Vida Salud</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="publico">
<header class="pub-nav">
    <a href="{{ route('portal') }}" class="brand brand-pub"><span class="brand-mark">+</span><span><strong>Clínica Vida Salud</strong><small>Santa Cruz de la Sierra</small></span></a>
    <nav>
        <a href="{{ route('portal') }}#especialidades">Especialidades</a>
        <a href="{{ route('portal') }}#medicos">Médicos</a>
        <a href="{{ route('portal.horarios') }}">Horarios disponibles</a>
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primario btn-sm">Mi panel</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Iniciar sesión</a>
            <a href="{{ route('registro') }}" class="btn btn-primario btn-sm">Registrarme</a>
        @endauth
    </nav>
</header>
<main>
    @yield('contenido')
</main>
<footer class="pub-pie">Clínica Vida Salud · Atención con fichas de 15 minutos · Lunes a sábado</footer>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
