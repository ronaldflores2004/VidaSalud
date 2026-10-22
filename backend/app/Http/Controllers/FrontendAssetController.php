<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Entrega los archivos estáticos (CSS y JS) de la carpeta frontend/assets.
 * Así el frontend queda separado del backend sin depender de enlaces simbólicos,
 * lo que funciona igual en XAMPP (Windows) y en Linux.
 */
class FrontendAssetController extends Controller
{
    private const TIPOS = ['css' => 'text/css', 'js' => 'application/javascript', 'png' => 'image/png', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon'];

    public function __invoke(string $ruta): BinaryFileResponse
    {
        $base = realpath(dirname(config('view.paths')[0]) . '/assets');
        $archivo = realpath($base . '/' . $ruta);

        // Evita salir de la carpeta assets (../) y entregar tipos no permitidos
        $extension = strtolower(pathinfo((string) $archivo, PATHINFO_EXTENSION));
        abort_unless($base && $archivo && str_starts_with($archivo, $base . DIRECTORY_SEPARATOR) && isset(self::TIPOS[$extension]), 404);

        return response()->file($archivo, [
            'Content-Type' => self::TIPOS[$extension],
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
