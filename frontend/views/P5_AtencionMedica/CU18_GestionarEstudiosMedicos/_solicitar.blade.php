{{-- CU18 Gestionar estudios médicos: solicitar otro estudio en una consulta registrada --}}
<form method="POST" action="{{ route('estudios.store', $consulta) }}" class="filtros" data-validar novalidate>
    @csrf
    <select name="catalogo_estudio_id" required>
        <option value="">Estudio a solicitar…</option>
        @foreach($catalogo as $e)<option value="{{ $e->id }}">{{ $e->nombre }} ({{ strtolower($e->categoria) }})</option>@endforeach
    </select>
    <input name="indicaciones" maxlength="255" placeholder="Indicaciones (opcional), ej. en ayunas">
    <button class="btn btn-sec">Solicitar estudio</button>
</form>
