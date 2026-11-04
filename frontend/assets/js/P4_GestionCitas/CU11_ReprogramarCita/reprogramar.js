/**
 * CU11 Reprogramar cita – muestra las fichas libres del mismo médico para la nueva fecha
 * (<<include>> CU9 Consultar disponibilidad). La propia cita se ignora para no verla ocupada.
 */
(function () {
    'use strict';
    const form = document.getElementById('form-reprogramar');
    if (!form) return;
    const fecha = document.getElementById('fecha'), hora = document.getElementById('hora_inicio');
    const caja = document.getElementById('bloques');
    const rFecha = document.getElementById('r-fecha'), rHora = document.getElementById('r-hora');

    async function cargar() {
        hora.value = ''; rHora.textContent = '—';
        rFecha.textContent = fecha.value ? fecha.value.split('-').reverse().join('/') : '—';
        if (!fecha.value) return;
        caja.innerHTML = '<p class="ayuda">Consultando disponibilidad…</p>';
        try {
            const r = await fetch(`${form.dataset.urlBloques}?fecha=${fecha.value}&ignorar=${form.dataset.cita}`,
                { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(x => x.json());
            if (!r.bloques.length) { caja.innerHTML = `<p class="ayuda">${r.mensaje || 'No hay fichas para esa fecha.'}</p>`; return; }
            caja.innerHTML = '<p class="ayuda">Elija la nueva ficha. Las grises están ocupadas o ya pasaron.</p><div class="slots">' +
                r.bloques.map(b => `<button type="button" class="slot" data-inicio="${b.inicio}" data-fin="${b.fin}" ${b.disponible ? '' : 'disabled'}>${b.inicio}</button>`).join('') + '</div>';
        } catch (e) { caja.innerHTML = '<p class="ayuda">No se pudo consultar la disponibilidad.</p>'; }
    }
    caja.addEventListener('click', function (e) {
        const b = e.target.closest('.slot');
        if (!b || b.disabled) return;
        caja.querySelectorAll('.slot').forEach(s => s.classList.remove('elegido'));
        b.classList.add('elegido');
        hora.value = b.dataset.inicio;
        rHora.textContent = b.dataset.inicio + ' – ' + b.dataset.fin;
    });
    fecha.addEventListener('change', cargar);
    if (fecha.value) cargar();
})();
