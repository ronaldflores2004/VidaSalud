<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control de acceso por rol (RN12).
 * Uso en rutas: ->middleware('rol:Administrador,Recepcionista')
 * Además cierra la sesión si el usuario fue desactivado mientras estaba conectado.
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->estaActivo()) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['login' => 'Su cuenta no está activa.']);
        }

        if ($roles && ! $usuario->tieneRol(...$roles)) {
            abort(403, 'No tiene permiso para acceder a esta opción.');
        }

        return $next($request);
    }
}
