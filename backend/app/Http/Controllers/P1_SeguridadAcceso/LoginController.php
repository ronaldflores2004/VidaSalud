<?php

namespace App\Http\Controllers\P1_SeguridadAcceso;

use App\Http\Controllers\Controller;
use App\Models\P1_SeguridadAcceso\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CU1 Iniciar sesión / CU2 Cerrar sesión.
 * El usuario puede ingresar con su nombre de usuario o con su correo electrónico.
 */
class LoginController extends Controller
{
    private const MAX_INTENTOS = 3;

    public function mostrar(): View
    {
        return view('P1_SeguridadAcceso.login');
    }

    public function ingresar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:100'],
        ], [], ['login' => 'usuario o correo', 'password' => 'contraseña']);

        // Bloqueo temporal después de 3 intentos fallidos (protección contra fuerza bruta)
        $clave = Str::lower($datos['login']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($clave);

            return back()->onlyInput('login')
                ->withErrors(['login' => "Demasiados intentos. Intente nuevamente en {$segundos} segundos."]);
        }

        $campo = filter_var($datos['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credenciales = [$campo => Str::lower($datos['login']), 'password' => $datos['password'], 'activo' => true];

        // Auth::attempt usa password_verify() internamente sobre el hash almacenado
        if (! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            RateLimiter::hit($clave, 60);

            // Mensaje genérico: no revela si el usuario existe o si está inactivo
            return back()->onlyInput('login')
                ->withErrors(['login' => 'Las credenciales no son válidas o la cuenta está inactiva.']);
        }

        RateLimiter::clear($clave);
        $request->session()->regenerate(); // evita fijación de sesión

        /** @var User $usuario */
        $usuario = Auth::user();
        $usuario->forceFill(['ultimo_acceso' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function salir(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('ok', 'Sesión cerrada correctamente.');
    }
}
