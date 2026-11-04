<?php

namespace App\Http\Controllers\P7_PagosNotificaciones\CU21_PagarFicha;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Models\P4_GestionCitas\Cita;
use App\Models\P7_PagosNotificaciones\Pago;
use App\Services\P7_PagosNotificaciones\CU21_PagarFicha\PagoService;
use App\Services\P7_PagosNotificaciones\CU21_PagarFicha\PaypalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CU21 Pagar ficha.
 *  - Paciente: paga en línea con PayPal desde su casa (create, orden, capturar).
 *  - Recepcionista: cobra en la clínica con efectivo, QR o tarjeta (cobrar), consulta los pagos
 *    y registra el reembolso de fichas pagadas que se cancelaron (index, reembolsar).
 */
class PagoController extends Controller
{
    public function __construct(private PagoService $pagos, private PaypalService $paypal)
    {
    }

    /** Pantalla «Pagar ficha» del paciente. */
    public function create(Request $request, Cita $cita): View|RedirectResponse
    {
        $this->soloSuya($request, $cita);
        $cita->load('paciente', 'medico', 'especialidad', 'pago');
        if ($cita->pago) {
            return redirect()->route('citas.show', $cita)->with('ok', "La ficha {$cita->codigo} ya está pagada.");
        }

        return view('P7_PagosNotificaciones.CU21_PagarFicha.pagar', [
            'cita' => $cita,
            'motivo' => $this->pagos->motivoNoPagable($cita),
            'montoUsd' => $this->pagos->montoUsd($cita),
            'tipoCambio' => $this->pagos->tipoCambio(),
            'paypalClientId' => $this->paypal->configurado() ? $this->paypal->clientId() : null,
        ]);
    }

    /** Paso 1 del botón de PayPal: crea la orden en el servidor y devuelve su id. */
    public function orden(Request $request, Cita $cita): JsonResponse
    {
        $this->soloSuya($request, $cita);
        try {
            if ($motivo = $this->pagos->motivoNoPagable($cita)) {
                throw new ReglaNegocioException($motivo);
            }

            return response()->json(['id' => $this->paypal->crearOrden($cita, $this->pagos->montoUsd($cita))]);
        } catch (ReglaNegocioException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /** Paso 2: el paciente aprobó el pago en PayPal; se captura y se registra. */
    public function capturar(Request $request, Cita $cita): JsonResponse
    {
        $this->soloSuya($request, $cita);
        try {
            $usd = $this->pagos->montoUsd($cita);
            if ($this->paypal->configurado()) {
                $datos = $request->validate(['orderID' => ['required', 'string', 'max:40']]);
                $captura = $this->paypal->capturar($datos['orderID'], $cita, $usd);
            } else {
                // Sin credenciales de PayPal: pago de prueba simulado (para demostraciones sin internet)
                $captura = ['referencia' => 'SIMULADO-' . Str::upper(Str::random(8)), 'monto_usd' => $usd];
            }
            $pago = $this->pagos->registrar($cita, 'PAYPAL', $captura['referencia'], $request->user(), $captura['monto_usd']);
        } catch (ReglaNegocioException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
        session()->flash('ok', "Pago {$pago->codigo} registrado. Su ficha {$cita->codigo} está pagada.");

        return response()->json(['ok' => true, 'redirect' => route('citas.show', $cita)]);
    }

    /** Cobro en recepción (efectivo, QR o tarjeta). */
    public function cobrar(Request $request, Cita $cita): RedirectResponse
    {
        $datos = $request->validate([
            'metodo' => ['required', 'in:EFECTIVO,QR,TARJETA'],
            'referencia' => ['nullable', 'required_unless:metodo,EFECTIVO', 'string', 'max:60'],
        ], ['referencia.required_unless' => 'Ingrese el número de comprobante del QR o del voucher de la tarjeta.']);

        try {
            $pago = $this->pagos->registrar($cita, $datos['metodo'], $datos['referencia'] ?? null, $request->user());
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', "Pago {$pago->codigo} registrado: Bs " . number_format($pago->monto, 2) . " ({$pago->metodo_texto}).");
    }

    /** Lista de pagos para recepción y administración. */
    public function index(Request $request): View
    {
        $pagos = Pago::with('cita.paciente', 'cita.especialidad', 'registradoPor')
            ->when($request->metodo, fn ($q, $m) => $q->where('metodo', $m))
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->when($request->fecha, fn ($q, $f) => $q->whereDate('pagado_en', $f))
            ->orderByDesc('pagado_en')->paginate(15)->withQueryString();

        return view('P7_PagosNotificaciones.CU21_PagarFicha.index', [
            'pagos' => $pagos,
            'totalHoy' => Pago::whereDate('pagado_en', today())->where('estado', Pago::PAGADO)->sum('monto'),
            'porReembolsar' => Pago::where('estado', Pago::POR_REEMBOLSAR)->count(),
        ]);
    }

    public function reembolsar(Request $request, Pago $pago): RedirectResponse
    {
        try {
            $this->pagos->reembolsar($pago, $request->user());
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', "Reembolso del pago {$pago->codigo} registrado.");
    }

    /** El paciente solo paga sus propias fichas (RN12). */
    private function soloSuya(Request $request, Cita $cita): void
    {
        abort_unless($cita->paciente_id === $request->user()->paciente?->id, 403, 'Solo puede pagar sus propias fichas.');
    }
}
