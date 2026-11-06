@extends('compartido.layouts.app')
@section('titulo', 'Política de citas')
@section('migas', 'Administración / Política de citas')

@section('contenido')
<div class="cabecera"><div><h1>Política de citas</h1>
    <p class="sub">Plazos que la clínica aplica a reservas, cancelaciones, reprogramaciones e inasistencias. Se cambian aquí, sin modificar el sistema.</p></div></div>
<form class="card form estrecho" method="POST" action="{{ route('politica.update') }}" data-validar novalidate>
    @csrf @method('PUT')
    @foreach($campos as $clave => [$etiqueta, $unidad, $min, $max])
        <label class="campo politica"><span>{{ $etiqueta }}</span>
            <span class="politica-fila">
                <input type="number" name="{{ $clave }}" value="{{ old($clave, $valores[$clave] ?? '') }}" required min="{{ $min }}" max="{{ $max }}" step="{{ $clave === 'TIPO_CAMBIO_USD' ? '0.01' : '1' }}">
                <small class="sub">{{ $unidad }}</small>
            </span></label>
    @endforeach
    <div class="form-pie"><button class="btn btn-primario">Guardar política</button></div>
</form>
@endsection
