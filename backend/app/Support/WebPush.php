<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Envío de notificaciones Web Push sin librerías externas (CU22).
 *
 * Estándares que implementa:
 *  - RFC 8292 (VAPID): el servidor se identifica ante el servicio push del navegador con un
 *    token JWT firmado con su clave privada (curva P-256, algoritmo ES256).
 *  - RFC 8291 / RFC 8188 (aes128gcm): el mensaje se cifra con las claves del navegador del
 *    usuario, así el servicio push (Google, Mozilla, Apple) no puede leerlo.
 *
 * Solo usa funciones de PHP: openssl (ECDH, firma y AES-GCM) y hash_hkdf.
 */
class WebPush
{
    /** Cabecera DER (SubjectPublicKeyInfo) de una clave pública P-256 sin comprimir. */
    private const SPKI_P256 = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    public function __construct(private string $clavePublica, private string $clavePrivadaPem, private string $asunto)
    {
    }

    public static function desdeConfig(): ?self
    {
        $c = config('services.webpush');
        if (empty($c['public_key']) || empty($c['private_key'])) {
            return null;
        }

        return new self($c['public_key'], base64_decode($c['private_key']), $c['subject'] ?: 'mailto:admin@vidasalud.bo');
    }

    /** Genera un par de claves VAPID: pública en base64url (para el navegador) y privada PEM en base64 (.env). */
    public static function generarClaves(): array
    {
        $clave = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        openssl_pkey_export($clave, $pem);

        return ['public' => self::b64u(self::puntoPublico($clave)), 'private' => base64_encode($pem)];
    }

    /**
     * Cifra y envía el mensaje a una suscripción. Devuelve el código HTTP del servicio push
     * (201 = aceptado; 404/410 = la suscripción ya no existe).
     */
    public function enviar(string $endpoint, string $p256dh, string $auth, string $mensaje, int $ttl = 86400): int
    {
        $cuerpo = $this->cifrar($mensaje, self::b64uDecode($p256dh), self::b64uDecode($auth));

        return Http::withHeaders([
            'Authorization' => 'vapid t=' . $this->jwt($endpoint) . ', k=' . $this->clavePublica,
            'Content-Encoding' => 'aes128gcm',
            'TTL' => (string) $ttl,
            'Urgency' => 'normal',
        ])->withBody($cuerpo, 'application/octet-stream')->timeout(5)->post($endpoint)->status();
    }

    /** Token VAPID: {aud: origen del servicio push, exp: 12 h, sub: contacto} firmado con ES256. */
    public function jwt(string $endpoint): string
    {
        $u = parse_url($endpoint);
        $aud = $u['scheme'] . '://' . $u['host'] . (isset($u['port']) ? ':' . $u['port'] : '');
        $datos = self::b64u(json_encode(['typ' => 'JWT', 'alg' => 'ES256'])) . '.'
            . self::b64u(json_encode(['aud' => $aud, 'exp' => time() + 43200, 'sub' => $this->asunto], JSON_UNESCAPED_SLASHES));
        if (! openssl_sign($datos, $der, $this->clavePrivadaPem, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar el token VAPID; revise VAPID_PRIVATE_KEY.');
        }

        return $datos . '.' . self::b64u(self::derARaw($der));
    }

    /** Cifrado aes128gcm (RFC 8291): devuelve cabecera + texto cifrado listo para enviar. */
    public function cifrar(string $mensaje, string $clavePublicaNavegador, string $secretoAuth): string
    {
        // 1. Clave temporal del servidor y secreto compartido ECDH con el navegador
        $local = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $localPublica = self::puntoPublico($local);
        $pemNavegador = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode(hex2bin(self::SPKI_P256) . $clavePublicaNavegador), 64, "\n") . "-----END PUBLIC KEY-----\n";
        $secreto = openssl_pkey_derive(openssl_pkey_get_public($pemNavegador), $local, 32);
        if ($secreto === false) {
            throw new RuntimeException('Clave p256dh del navegador inválida.');
        }

        // 2. Derivación de claves con HKDF-SHA256
        $ikm = hash_hkdf('sha256', $secreto, 32, "WebPush: info\0" . $clavePublicaNavegador . $localPublica, $secretoAuth);
        $sal = random_bytes(16);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $sal);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $sal);

        // 3. AES-128-GCM sobre el mensaje + delimitador del último registro (0x02)
        $cifrado = openssl_encrypt($mensaje . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

        // Cabecera: sal (16) | tamaño de registro (4) | largo de la clave (1) | clave pública temporal (65)
        return $sal . pack('N', 4096) . chr(strlen($localPublica)) . $localPublica . $cifrado . $tag;
    }

    /** Punto público sin comprimir (0x04 | X | Y) de una clave P-256. */
    private static function puntoPublico($clave): string
    {
        $ec = openssl_pkey_get_details($clave)['ec'];

        return "\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);
    }

    /** Firma ECDSA en DER (SEQUENCE{r, s}) → formato JWS (r | s, 64 bytes). */
    private static function derARaw(string $der): string
    {
        $pos = 2;
        if (ord($der[1]) & 0x80) {
            $pos += ord($der[1]) & 0x7F;
        }
        $partes = [];
        for ($i = 0; $i < 2; $i++) {
            $largo = ord($der[$pos + 1]);
            $valor = ltrim(substr($der, $pos + 2, $largo), "\0");
            $partes[] = str_pad($valor, 32, "\0", STR_PAD_LEFT);
            $pos += 2 + $largo;
        }

        return $partes[0] . $partes[1];
    }

    public static function b64u(string $datos): string
    {
        return rtrim(strtr(base64_encode($datos), '+/', '-_'), '=');
    }

    public static function b64uDecode(string $datos): string
    {
        return base64_decode(strtr($datos, '-_', '+/') . str_repeat('=', (4 - strlen($datos) % 4) % 4));
    }
}
