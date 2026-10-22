/**
 * CU9 Consultar disponibilidad (portal público): muestra las fichas libres de un médico sin iniciar sesión.
 */
(function () {
    'use strict';
    const caja = document.getElementById('consulta-horarios');
    if (!caja) return;
    const esp = document.getElementById('especialidad_id'), med = document.getElementById('medico_id'),
          fecha = document.getElementById('fecha'), bloques = document.getElementById('bloques'), dias = document.getElementById('dias-medico');
    const json = url => fetch(url, { headers: { Accept: 'application/json' } }).then(r => { if (!r.ok) throw r; return r.json(); });

    esp.addEventListener('change', async function () {
        med.disabled = true; dias.textContent = '';
        bloques.innerHTML = '<p class="ayuda">Elija médico y fecha para ver las fichas.</p>';
        if (!esp.value) { med.innerHTML = '<option value="">Primero elija la especialidad</option>'; return; }
        const lista = await json(`${caja.dataset.urlMedicos}/${esp.value}/medicos`);
        med.innerHTML = '<option value="">Seleccione…</option>' + lista.map(m => `<option value="${m.id}" data-dias="${m.dias.join(', ')}">${m.nombre}</option>`).join('');
        med.disabled = false;
    });
    async function cargar() {
        const op = med.options[med.selectedIndex];
        dias.textContent = med.value ? 'Atiende: ' + (op.dataset.dias || 'sin horario') : '';
        if (!med.value || !fecha.value) return;
        bloques.innerHTML = '<p class="ayuda">Consultando…</p>';
        try {
            const r = await json(`${caja.dataset.urlBloques}/${med.value}/bloques?fecha=${fecha.value}`);
            if (!r.bloques.length) { bloques.innerHTML = `<p class="ayuda">${r.mensaje || 'Sin fichas para esa fecha.'}</p>`; return; }
            const libres = r.bloques.filter(b => b.disponible).length;
            bloques.innerHTML = `<p class="ayuda">${libres} ficha(s) libre(s) de 15 minutos.</p><div class="slots">` +
                r.bloques.map(b => `<span class="slot ${b.disponible ? '' : 'ocupado'}">${b.inicio}</span>`).join('') + '</div>';
        } catch (e) { bloques.innerHTML = '<p class="ayuda">No se pudo consultar. Intente de nuevo.</p>'; }
    }
    med.addEventListener('change', cargar);
    fecha.addEventListener('change', cargar);
})();
