{{-- CU17 Registrar tratamiento: agregar un medicamento a una consulta ya registrada --}}
<form method="POST" action="{{ route('tratamientos.store', $consulta) }}" class="fila-trat" data-validar novalidate>
    @csrf
    <label class="campo"><span>Medicamento *</span>
        <select name="medicamento_id" required>
            <option value="">Seleccione…</option>
            @foreach($medicamentos as $m)<option value="{{ $m->id }}">{{ $m->nombre }} · {{ $m->presentacion }}</option>@endforeach
        </select></label>
    <label class="campo"><span>Dosis *</span><input name="dosis" required maxlength="60" placeholder="1 tableta"></label>
    <label class="campo"><span>Frecuencia *</span><input name="frecuencia" required maxlength="60" placeholder="cada 8 h"></label>
    <label class="campo"><span>Duración *</span><input name="duracion" required maxlength="60" placeholder="5 días"></label>
    <label class="campo"><span>Indicaciones</span><input name="indicaciones" maxlength="255"></label>
    <button class="btn btn-sec btn-sm">Agregar</button>
</form>
