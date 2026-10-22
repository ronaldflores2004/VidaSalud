@extends('layouts.publico')
@section('titulo', 'Registro de paciente')

@section('contenido')
<section class="pub-seccion estrecho-centro">
    <h1>Registro de paciente</h1>
    <p class="sub">Complete sus datos para crear su cuenta y reservar fichas desde la web. Los campos con * son obligatorios.</p>

    @if($errors->any())
        <div class="alerta alerta-error"><strong>Revise los datos:</strong><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form class="card form" method="POST" action="{{ route('registro.guardar') }}" data-validar novalidate>
        @csrf
        <h2>Datos personales</h2>
        <div class="grid-2">
            <label class="campo"><span>CI *</span>
                <input name="ci" value="{{ old('ci') }}" required maxlength="16" pattern="[0-9]{5,12}(-?[A-Za-z0-9]{1,3})?" data-msg="Entre 5 y 12 dígitos (ej. 8456123 o 8456123-1B)."></label>
            <label class="campo"><span>Fecha de nacimiento *</span>
                <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" required max="{{ today()->toDateString() }}" min="1900-01-02"></label>
            <label class="campo"><span>Nombres *</span>
                <input name="nombres" value="{{ old('nombres') }}" required minlength="2" maxlength="80" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+" data-msg="Solo letras."></label>
            <label class="campo"><span>Apellidos *</span>
                <input name="apellidos" value="{{ old('apellidos') }}" required minlength="2" maxlength="80" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+" data-msg="Solo letras."></label>
            <label class="campo"><span>Sexo *</span>
                <select name="sexo" required><option value="">Seleccione…</option>
                    @foreach(['F' => 'Femenino', 'M' => 'Masculino', 'O' => 'Otro'] as $k => $v)<option value="{{ $k }}" @selected(old('sexo') === $k)>{{ $v }}</option>@endforeach
                </select></label>
            <label class="campo"><span>Teléfono / celular *</span>
                <input name="telefono" value="{{ old('telefono') }}" required pattern="[0-9+\s\-]{7,20}" data-msg="Entre 7 y 20 dígitos."></label>
        </div>
        <h2>Cuenta</h2>
        <div class="grid-2">
            <label class="campo"><span>Correo electrónico *</span>
                <input type="email" name="email" value="{{ old('email') }}" required maxlength="120"></label>
            <span></span>
            <label class="campo"><span>Contraseña *</span>
                <input type="password" name="password" required minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" data-msg="Mínimo 8 caracteres con letras y números." autocomplete="new-password"></label>
            <label class="campo"><span>Confirmar contraseña *</span>
                <input type="password" name="password_confirmation" required data-igual="password" autocomplete="new-password"></label>
        </div>
        <label class="check"><input type="checkbox" name="acepta" value="1" required @checked(old('acepta'))> Acepto que la clínica use mis datos para mi atención médica.</label>
        <div class="form-pie">
            <a href="{{ route('login') }}" class="btn btn-ghost">Ya tengo cuenta</a>
            <button class="btn btn-primario">Crear mi cuenta</button>
        </div>
    </form>
</section>
@endsection
