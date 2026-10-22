/**
 * Clínica Vida Salud – utilidades de interfaz.
 * Validación del lado del cliente (el servidor vuelve a validar todo),
 * confirmaciones, mostrar contraseña y menú en pantallas pequeñas.
 */
(function () {
    'use strict';

    /** Muestra u oculta el mensaje de error debajo de un campo. */
    function marcar(campo, mensaje) {
        const contenedor = campo.closest('.campo') || campo.parentElement;
        let aviso = contenedor.querySelector('.error-campo');
        if (mensaje) {
            if (!aviso) {
                aviso = document.createElement('small');
                aviso.className = 'error-campo';
                contenedor.appendChild(aviso);
            }
            aviso.textContent = mensaje;
            campo.classList.add('invalido');
        } else {
            if (aviso) aviso.remove();
            campo.classList.remove('invalido');
        }
    }

    /** Devuelve el mensaje de error de un campo o cadena vacía si es válido. */
    function validarCampo(campo, form) {
        if (campo.disabled) return '';
        const v = campo.validity;
        const valor = (campo.value || '').trim();

        if (v.valueMissing || (campo.required && valor === '')) return campo.dataset.msg && campo.type === 'hidden' ? campo.dataset.msg : 'Este campo es obligatorio.';
        if (v.typeMismatch && campo.type === 'email') return 'Ingrese un correo electrónico válido.';
        if (v.tooShort) return 'Debe tener al menos ' + campo.minLength + ' caracteres.';
        if (v.patternMismatch) return campo.dataset.msg || 'El formato no es válido.';
        if (v.rangeUnderflow || v.rangeOverflow) return 'La fecha está fuera del rango permitido.';

        if (campo.dataset.igual) {
            const otro = form.querySelector('[name="' + campo.dataset.igual + '"]');
            if (otro && otro.value !== campo.value) return 'Las contraseñas no coinciden.';
        }
        if (campo.dataset.despuesDe) {
            const otro = form.querySelector('[name="' + campo.dataset.despuesDe + '"]');
            if (otro && otro.value && valor && valor <= otro.value) return 'Debe ser posterior a la hora inicial.';
        }
        return '';
    }

    document.querySelectorAll('form[data-validar]').forEach(function (form) {
        const campos = () => form.querySelectorAll('input, select, textarea');

        campos().forEach(function (c) {
            c.addEventListener('blur', () => marcar(c, validarCampo(c, form)));
            c.addEventListener('input', () => { if (c.classList.contains('invalido')) marcar(c, validarCampo(c, form)); });
        });

        form.addEventListener('submit', function (e) {
            let primero = null;
            campos().forEach(function (c) {
                const msg = validarCampo(c, form);
                marcar(c, msg);
                if (msg && !primero) primero = c;
            });
            // Grupos de casillas: al menos una marcada
            form.querySelectorAll('[data-requerir-uno]').forEach(function (g) {
                const ok = g.querySelector('input:checked');
                marcar(g, ok ? '' : g.dataset.requerirUno);
                if (!ok && !primero) primero = g;
            });
            if (primero) {
                e.preventDefault();
                (primero.type === 'hidden' ? primero.parentElement : primero).scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (primero.focus && primero.type !== 'hidden') primero.focus();
                return;
            }
            // Evita doble envío
            const btn = form.querySelector('button[type="submit"], button:not([type])');
            if (btn) { btn.disabled = true; btn.dataset.texto = btn.textContent; btn.textContent = 'Guardando…'; }
        });
    });

    // Confirmación antes de acciones sensibles (desactivar, cerrar sesión, etc.)
    document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.dataset.confirmar)) e.preventDefault();
        });
    });

    // Mostrar / ocultar contraseña
    document.querySelectorAll('[data-ver-clave]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.dataset.verClave);
            const oculto = input.type === 'password';
            input.type = oculto ? 'text' : 'password';
            btn.textContent = oculto ? 'Ocultar' : 'Mostrar';
        });
    });

    // Menú lateral en móviles
    const toggle = document.querySelector('[data-toggle-menu]');
    if (toggle) toggle.addEventListener('click', () => document.getElementById('sidebar').classList.toggle('abierto'));

    // Las alertas de éxito desaparecen solas
    document.querySelectorAll('.alerta-ok').forEach(a => setTimeout(() => a.classList.add('oculta'), 5000));
})();
