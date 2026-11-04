/**
 * CU21 Pagar ficha – botón de PayPal.
 * El monto y la orden se crean en el servidor (no en el navegador) para que no se puedan alterar.
 *   createOrder → POST .../pago/paypal/orden     (el servidor crea la orden en PayPal)
 *   onApprove   → POST .../pago/paypal/capturar  (el servidor cobra la orden y registra el pago)
 * Sin credenciales de PayPal se muestra el botón «simulado», que llama directo a capturar.
 */
(function () {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const error = document.getElementById('pago-error');

    function mostrarError(texto) {
        if (!error) return alert(texto);
        error.textContent = texto;
        error.hidden = false;
    }

    async function post(url, datos) {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify(datos || {}),
        });
        const json = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(json.error || json.message || 'No se pudo completar el pago.');
        return json;
    }

    const contenedor = document.getElementById('paypal-botones');
    if (contenedor && window.paypal) {
        window.paypal.Buttons({
            style: { layout: 'vertical', shape: 'rect', label: 'pay' },
            createOrder: () => post(contenedor.dataset.orden).then((d) => d.id),
            onApprove: (data) => post(contenedor.dataset.capturar, { orderID: data.orderID })
                .then((d) => { window.location = d.redirect; }),
            onCancel: () => mostrarError('Pago cancelado. Su ficha sigue reservada y puede pagarla más tarde.'),
            onError: (e) => mostrarError(e.message || 'PayPal no pudo procesar el pago.'),
        }).render(contenedor);
    } else if (contenedor) {
        mostrarError('No se pudo cargar PayPal. Verifique su conexión a internet.');
    }

    const simulado = document.getElementById('pago-simulado');
    simulado?.addEventListener('click', async () => {
        simulado.disabled = true;
        simulado.textContent = 'Procesando pago…';
        try {
            const d = await post(simulado.dataset.capturar);
            window.location = d.redirect;
        } catch (e) {
            mostrarError(e.message);
            simulado.disabled = false;
            simulado.textContent = 'Pagar con PayPal (simulado)';
        }
    });
})();
