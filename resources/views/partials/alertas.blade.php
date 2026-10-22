@if(session('ok'))
    <div class="alerta alerta-ok" role="status">{{ session('ok') }}</div>
@endif
@if(session('error'))
    <div class="alerta alerta-error" role="alert">{{ session('error') }}</div>
@endif
@if($errors->any() && ! request()->routeIs('login'))
    <div class="alerta alerta-error" role="alert">
        <strong>Revise los datos del formulario:</strong>
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
