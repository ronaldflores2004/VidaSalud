<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Control de acceso por rol (RN12)
        $middleware->alias(['rol' => \App\Http\Middleware\P1_SeguridadAcceso\VerificarRol::class]);
        // Sin sesión válida se redirige al login (prueba 9)
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Con APP_DEBUG=false los errores muestran una página amigable (resources/views/errors)
        // sin credenciales ni detalles internos.
    })->create();
