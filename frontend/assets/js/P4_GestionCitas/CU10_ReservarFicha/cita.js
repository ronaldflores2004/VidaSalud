/**
 * CU10 Reservar ficha médica – interacción del formulario.
 * 1) Busca pacientes, 2) carga médicos por especialidad, 3) muestra los bloques libres
 * del médico para la fecha elegida (CU9 Consultar disponibilidad).
 */
(function () {
    'use strict';
    const form = document.getElementById('form-cita');
    if (!form) return;

    const $ = id => document.getElementById(id);
    const urlMedicos = form.dataset.urlMedicos;
    const urlBloques = form.dataset.urlBloques;
    const urlPacientes = form.dataset.urlPacientes;

    const esp = $('especialidad_id'), med = $('medico_id'), fecha = $('fecha'), hora = $('hora_inicio');
    const caja = $('bloques');

    async function getJSON(url) {
        const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!r.ok) throw new Error('Error ' + r.status);
        return r.json();
    }

    function texto(el, valor) { el.textContent = valor || '—'; }

    // ---------- 1. Paciente ----------
    const buscar = $('buscar-paciente'), lista = $('sugerencias');
    let espera;
    // Cuando reserva el propio paciente no hay buscador
    if (buscar) buscar.addEventListener('input', function () {
        $('paciente_id').value = '';
        texto($('r-paciente'), '');
        clearTimeout(espera);
        const q = buscar.value.trim();
        if (q.length < 2) { lista.hidden = true; return; }
        espera = setTimeout(async function () {
            try {
                const datos = await getJSON(urlPacientes + '?q=' + encodeURIComponent(q));
                lista.innerHTML = datos.length
                    ? datos.map(p => `<li data-id="${p.id}" data-nombre="${p.nombre}"><strong>${p.nombre}</strong><small>${p.codigo} · CI ${p.ci || '—'}</small></li>`).join('')
                    : '<li class="nada">Sin resultados</li>';
                lista.hidden = false;
            } catch (e) { lista.innerHTML = '<li class="nada">No se pudo buscar</li>'; lista.hidden = false; }
        }, 250);
    });
    if (lista) lista.addEventListener('click', function (e) {
        const li = e.target.closest('li[data-id]');
        if (!li) return;
        $('paciente_id').value = li.dataset.id;
        buscar.value = li.dataset.nombre;
        texto($('r-paciente'), li.dataset.nombre);
        lista.hidden = true;
        buscar.classList.remove('invalido');
        const err = buscar.closest('.paso').querySelector('.error-campo'); if (err) err.remove();
    });

    // ---------- 2. Médicos por especialidad ----------
    async function cargarMedicos() {
        med.innerHTML = '<option value="">Cargando…</option>';
        med.disabled = true;
        limpiarBloques('Elija médico y fecha para ver los horarios.');
        texto($('r-especialidad'), esp.value ? esp.options[esp.selectedIndex].text : '');
        texto($('r-medico'), '');
        $('dias-medico').textContent = '';
        if (!esp.value) { med.innerHTML = '<option value="">Primero elija la especialidad</option>'; return; }

        const medicos = await getJSON(`${urlMedicos}/${esp.value}/medicos`);
        med.innerHTML = '<option value="">Seleccione…</option>' +
            medicos.map(m => `<option value="${m.id}" data-dias="${m.dias.join(', ')}">${m.nombre}</option>`).join('');
        med.disabled = false;
        if (med.dataset.old) { med.value = med.dataset.old; med.dataset.old = ''; alCambiarMedico(); }
    }

    function alCambiarMedico() {
        const op = med.options[med.selectedIndex];
        texto($('r-medico'), med.value ? op.text : '');
        $('dias-medico').textContent = med.value ? 'Atiende: ' + (op.dataset.dias || 'sin horarios registrados') : '';
        cargarBloques();
    }

    // ---------- 3. Bloques disponibles ----------
    function limpiarBloques(msg) {
        hora.value = '';
        texto($('r-hora'), '');
        caja.innerHTML = `<p class="ayuda">${msg}</p>`;
    }

    async function cargarBloques() {
        const f = fecha.value;
        texto($('r-fecha'), f ? f.split('-').reverse().join('/') : '');
        if (!med.value || !f) { limpiarBloques('Elija médico y fecha para ver los horarios.'); return; }
        caja.innerHTML = '<p class="ayuda">Consultando disponibilidad…</p>';
        const previo = hora.value;
        try {
            const r = await getJSON(`${urlBloques}/${med.value}/bloques?fecha=${f}`);
            if (!r.bloques.length) { limpiarBloques(r.mensaje || 'No hay horarios para esa fecha.'); return; }
            const libres = r.bloques.filter(b => b.disponible).length;
            caja.innerHTML = `<p class="ayuda">${libres} horario(s) libre(s). Los grises ya están ocupados o pasaron.</p><div class="slots">` +
                r.bloques.map(b => `<button type="button" class="slot" data-inicio="${b.inicio}" data-fin="${b.fin}" ${b.disponible ? '' : 'disabled'}>${b.inicio}</button>`).join('') +
                '</div>';
            hora.value = '';
            if (previo) { const b = caja.querySelector(`.slot[data-inicio="${previo}"]:not([disabled])`); if (b) b.click(); }
        } catch (e) {
            limpiarBloques('No se pudo consultar la disponibilidad. Intente de nuevo.');
        }
    }

    caja.addEventListener('click', function (e) {
        const b = e.target.closest('.slot');
        if (!b || b.disabled) return;
        caja.querySelectorAll('.slot').forEach(s => s.classList.remove('elegido'));
        b.classList.add('elegido');
        hora.value = b.dataset.inicio;
        texto($('r-hora'), b.dataset.inicio + ' – ' + b.dataset.fin);
        const err = caja.parentElement.querySelector('.error-campo'); if (err) err.remove();
    });

    esp.addEventListener('change', cargarMedicos);
    med.addEventListener('change', alCambiarMedico);
    fecha.addEventListener('change', cargarBloques);

    if (esp.value) cargarMedicos();
})();
