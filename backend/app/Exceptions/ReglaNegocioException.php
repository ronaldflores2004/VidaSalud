<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando una operación viola una regla de negocio (RN01..RN14).
 * El mensaje es apto para mostrarse al usuario: no contiene detalles internos.
 */
class ReglaNegocioException extends RuntimeException
{
}
