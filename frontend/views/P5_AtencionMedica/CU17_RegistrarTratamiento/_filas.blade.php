{{-- CU17 Registrar tratamiento: filas de medicamentos dentro del formulario de la consulta (<<extend>> de CU15) --}}
<div id="tratamientos" data-siguiente="{{ max(3, count(old('tratamientos', []))) }}">
    @foreach(old('tratamientos', [[], [], []]) as $i => $t)
    <div class="fila-trat">
        <label class="campo"><span>Medicamento</span>
            <select name="tratamientos[{{ $i }}][medicamento_id]">
                <option value="">—</option>
                @foreach($medicamentos as $m)<option value="{{ $m->id }}" @selected(($t['medicamento_id'] ?? null) == $m->id)>{{ $m->nombre }} · {{ $m->presentacion }}</option>@endforeach
            </select></label>
        <label class="campo"><span>Dosis</span><input name="tratamientos[{{ $i }}][dosis]" value="{{ $t['dosis'] ?? '' }}" maxlength="60" placeholder="1 tableta"></label>
        <label class="campo"><span>Frecuencia</span><input name="tratamientos[{{ $i }}][frecuencia]" value="{{ $t['frecuencia'] ?? '' }}" maxlength="60" placeholder="cada 8 h"></label>
        <label class="campo"><span>Duración</span><input name="tratamientos[{{ $i }}][duracion]" value="{{ $t['duracion'] ?? '' }}" maxlength="60" placeholder="5 días"></label>
        <label class="campo"><span>Indicaciones</span><input name="tratamientos[{{ $i }}][indicaciones]" value="{{ $t['indicaciones'] ?? '' }}" maxlength="255" placeholder="Después de comer"></label>
        <button type="button" class="btn btn-ghost btn-sm" data-quitar-fila title="Quitar">✕</button>
    </div>
    @endforeach
</div>
<button type="button" class="btn btn-ghost btn-sm" id="agregar-tratamiento">+ Agregar medicamento</button>
