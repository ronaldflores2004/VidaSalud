/**
 * CU17 Registrar tratamiento – agrega o quita filas de medicamentos en el formulario de la consulta.
 */
(function () {
    'use strict';
    const caja = document.getElementById('tratamientos');
    const boton = document.getElementById('agregar-tratamiento');
    if (!caja || !boton) return;
    let n = parseInt(caja.dataset.siguiente, 10) || caja.children.length;

    boton.addEventListener('click', function () {
        if (caja.children.length >= 10) return;
        const fila = caja.firstElementChild.cloneNode(true);
        fila.querySelectorAll('input, select').forEach(function (el) {
            el.name = el.name.replace(/tratamientos\[\d+\]/, 'tratamientos[' + n + ']');
            el.value = '';
        });
        caja.appendChild(fila);
        n++;
    });
    caja.addEventListener('click', function (e) {
        if (!e.target.closest('[data-quitar-fila]')) return;
        const fila = e.target.closest('.fila-trat');
        if (caja.children.length > 1) fila.remove();
        else fila.querySelectorAll('input, select').forEach(el => { el.value = ''; });
    });
})();
