<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · Clínica Vida Salud</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="login-body">
<div class="login-wrap">
    <section class="login-panel">
        <div class="brand brand-grande"><span class="brand-mark">+</span><span><strong>Clínica Vida Salud</strong><small>Sistema de gestión de citas médicas</small></span></div>
        <p class="login-texto">Programe citas sin cruces de horario, mantenga el historial de cada paciente y consulte la agenda de los médicos en un solo lugar.</p>
        <ul class="login-lista">
            <li>Disponibilidad calculada según el horario de cada médico</li>
            <li>Historial de citas conservado, sin borrados</li>
            <li>Acceso según el rol de cada usuario</li>
        </ul>
    </section>

    <section class="login-card">
        <h1>Iniciar sesión</h1>
        <p class="sub">Personal: usuario o correo. Pacientes: CI o correo.</p>

        @if(session('ok'))<div class="alerta alerta-ok">{{ session('ok') }}</div>@endif
        @error('login')<div class="alerta alerta-error">{{ $message }}</div>@enderror

        <form method="POST" action="{{ route('login.ingresar') }}" data-validar novalidate>
            @csrf
            <label class="campo">
                <span>Usuario o correo electrónico</span>
                <input type="text" name="login" value="{{ old('login') }}" required maxlength="120" autocomplete="username" autofocus>
            </label>
            <label class="campo">
                <span>Contraseña</span>
                <div class="input-grupo">
                    <input type="password" name="password" required maxlength="100" autocomplete="current-password" id="password">
                    <button type="button" class="btn btn-ghost" data-ver-clave="password">Mostrar</button>
                </div>
            </label>
            <label class="check"><input type="checkbox" name="recordar" value="1"> Recordarme en este equipo</label>
            <button type="submit" class="btn btn-primario btn-bloque">Ingresar</button>
        </form>
        <p class="login-pie">¿Es paciente y aún no tiene cuenta? <a href="{{ route('registro') }}">Regístrese aquí</a><br>
            <a href="{{ route('portal') }}">← Volver a la página de la clínica</a></p>
    </section>
</div>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
