<?php

namespace App\Libraries;

/**
 * M-1: cifrado en reposo de datos personales del modulo de proteccion de datos.
 *
 * AEAD autenticado (AES-256-GCM) con nonce aleatorio por operacion y version de
 * clave por campo para permitir rotacion. Formato de campo cifrado:
 *
 *     v{keyVersion}.{base64(nonce(12) . tag(16) . ciphertext)}
 *
 * Principio rector: el cifrado es SOLO una capa de almacenamiento. Todos los hashes
 * probatorios (instancia_hash, evento_hash, etc.) se calculan sobre el texto plano;
 * por eso descifrar-al-leer no altera ninguna evidencia.
 *
 * Las claves viven fuera de la base de datos y del repositorio. privacy.encKey
 * (hex de 32 bytes) tiene prioridad; instalaciones existentes pueden derivar una
 * subclave separada mediante HKDF desde encryption.key. Nunca se registran.
 */
final class PrivacyCipher
{
    private const CIPHER = 'aes-256-gcm';
    private const NONCE_BYTES = 12;
    private const TAG_BYTES = 16;
    private const CURRENT_VERSION = 1;
    private const PREFIX = '/^v(\d+)\.([A-Za-z0-9+\/=]+)$/';

    /** @var array<int,string> version => clave binaria (32 bytes) */
    private array $keys;

    private int $currentVersion;

    public function __construct(?array $keys = null, ?int $currentVersion = null)
    {
        $this->currentVersion = $currentVersion ?? (int) (env('privacy.encVersion') ?: self::CURRENT_VERSION);
        $this->keys = $keys ?? self::loadKeysFromEnv($this->currentVersion);
        if (! isset($this->keys[$this->currentVersion])) {
            throw new \RuntimeException('No hay clave de cifrado para la version actual (privacy.encKey).');
        }
        foreach ($this->keys as $version => $key) {
            if (strlen($key) !== 32) {
                throw new \RuntimeException('La clave de cifrado privacy.encKey v' . $version . ' debe ser de 32 bytes.');
            }
        }
    }

    /**
     * @return array<int,string>
     */
    private static function loadKeysFromEnv(int $currentVersion): array
    {
        $keys = [];
        // privacy.encKey siempre es la clave activa; las anteriores se conservan
        // como privacy.encKey.vN para permitir descifrado y rotacion controlada.
        $primary = (string) env('privacy.encKey');
        if ($primary === '') {
            $master = (string) env('encryption.key');
            if (strlen($master) < 32) {
                throw new \RuntimeException('Falta privacy.encKey y encryption.key no permite derivar una subclave segura.');
            }
            $keys[$currentVersion] = hash_hkdf('sha256', $master, 32, 'censo/privacy/encryption/v' . $currentVersion);
        } elseif (! ctype_xdigit($primary) || strlen($primary) !== 64) {
            throw new \RuntimeException('privacy.encKey invalida (se esperan 64 hex = 32 bytes).');
        } else {
            $keys[$currentVersion] = hex2bin($primary);
        }
        for ($v = 1; $v <= 8; $v++) {
            if ($v === $currentVersion) {
                continue;
            }
            $extra = (string) env('privacy.encKey.v' . $v);
            if ($extra !== '' && ctype_xdigit($extra) && strlen($extra) === 64) {
                $keys[$v] = hex2bin($extra);
            }
        }
        return $keys;
    }

    public function keyVersion(): int
    {
        return $this->currentVersion;
    }

    public function isEncrypted(?string $value): bool
    {
        return $value !== null && $value !== '' && preg_match(self::PREFIX, $value) === 1;
    }

    /**
     * Cifra un valor de texto plano. Los valores nulos o vacios se devuelven tal cual
     * (no hay nada que proteger y se preserva la semantica NULL de la columna).
     */
    public function encrypt(?string $plaintext, string $aad = ''): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }
        if ($this->isEncrypted($plaintext)) {
            return $plaintext; // idempotente: no recifrar
        }
        $nonce = random_bytes(self::NONCE_BYTES);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $this->keys[$this->currentVersion], OPENSSL_RAW_DATA, $nonce, $tag, $aad, self::TAG_BYTES);
        if ($ciphertext === false) {
            throw new \RuntimeException('Fallo el cifrado AEAD.');
        }
        return 'v' . $this->currentVersion . '.' . base64_encode($nonce . $tag . $ciphertext);
    }

    /**
     * Descifra un valor. Si no tiene el formato cifrado, se asume texto plano (compat.
     * con filas aun no migradas) y se devuelve intacto.
     */
    public function decrypt(?string $value, string $aad = ''): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        if (preg_match(self::PREFIX, $value, $match) !== 1) {
            return $value; // texto plano heredado
        }
        $version = (int) $match[1];
        if (! isset($this->keys[$version])) {
            throw new \RuntimeException('No hay clave para descifrar la version v' . $version . '.');
        }
        $raw = base64_decode($match[2], true);
        if ($raw === false || strlen($raw) < self::NONCE_BYTES + self::TAG_BYTES) {
            throw new \RuntimeException('Valor cifrado corrupto o truncado.');
        }
        $nonce = substr($raw, 0, self::NONCE_BYTES);
        $tag = substr($raw, self::NONCE_BYTES, self::TAG_BYTES);
        $ciphertext = substr($raw, self::NONCE_BYTES + self::TAG_BYTES);
        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $this->keys[$version], OPENSSL_RAW_DATA, $nonce, $tag, $aad);
        if ($plaintext === false) {
            throw new \RuntimeException('Fallo la verificacion AEAD al descifrar (dato manipulado o clave incorrecta).');
        }
        return $plaintext;
    }

    /**
     * Cifra el contenido binario de un archivo (soportes subidos por el Titular).
     */
    public function encryptBinary(string $binary, string $aad = ''): string
    {
        $nonce = random_bytes(self::NONCE_BYTES);
        $tag = '';
        $ciphertext = openssl_encrypt($binary, self::CIPHER, $this->keys[$this->currentVersion], OPENSSL_RAW_DATA, $nonce, $tag, $aad, self::TAG_BYTES);
        if ($ciphertext === false) {
            throw new \RuntimeException('Fallo el cifrado del archivo.');
        }
        return "PRIVENC1\0" . chr($this->currentVersion) . $nonce . $tag . $ciphertext;
    }

    public function decryptBinary(string $blob, string $aad = ''): string
    {
        $header = "PRIVENC1\0";
        if (! str_starts_with($blob, $header)) {
            return $blob; // archivo heredado sin cifrar
        }
        $offset = strlen($header);
        $version = ord($blob[$offset]);
        if (! isset($this->keys[$version])) {
            throw new \RuntimeException('No hay clave para descifrar el archivo v' . $version . '.');
        }
        $offset += 1;
        $nonce = substr($blob, $offset, self::NONCE_BYTES);
        $tag = substr($blob, $offset + self::NONCE_BYTES, self::TAG_BYTES);
        $ciphertext = substr($blob, $offset + self::NONCE_BYTES + self::TAG_BYTES);
        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $this->keys[$version], OPENSSL_RAW_DATA, $nonce, $tag, $aad);
        if ($plaintext === false) {
            throw new \RuntimeException('Fallo la verificacion AEAD del archivo.');
        }
        return $plaintext;
    }
}
