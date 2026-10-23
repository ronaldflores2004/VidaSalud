<?php

namespace App\Http\Controllers\P1_SeguridadAcceso\CU2_CerrarSesion;

use App\Http\Controllers\Controller;
use App\Models\P1_SeguridadAcceso\Bitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CU2 Cerrar sesión.
 * Registra la salida en la bitácora, invalida la sesión y regenera el token CSRF.
 */
class LogoutController extends Controller
{
    public function salir(Request $request): RedirectResponse
    {
        Bitacora::registrar('LOGOUT', 'P1_SeguridadAcceso', $request->user(), 'Cierre de sesión');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('ok', 'Sesión cerrada correctamente.');
    }
}
