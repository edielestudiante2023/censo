<?php

namespace App\Libraries;

/**
 * M-1: indices ciegos (blind index) para permitir busquedas por igualdad sobre
 * datos cifrados sin cifrado determinista.
 *
 * blindIndex = HMAC-SHA256(K_idx, normalizar(valor)) con una clave (privacy.indexKey)
 * INDEPENDIENTE de la clave de cifrado. Puede configurarse directamente o derivarse
 * con un dominio HKDF exclusivo desde encryption.key. La normalizacion es explicita para
 * que "CC 1.234" y "1234" no generen indices distintos por accidente de formato.
 */
final class PrivacyPii
{
    private string $indexKey;

    public function __construct(?string $indexKey = null)
    {
        $key = $indexKey ?? (string) env('privacy.indexKey');
        if ($key === '') {
            $master = (string) env('encryption.key');
            if (strlen($master) < 32) {
                throw new \RuntimeException('Falta privacy.indexKey y encryption.key no permite derivar una subclave segura.');
            }
            $this->indexKey = hash_hkdf('sha256', $master, 32, 'censo/privacy/blind-index/v1');
            return;
        }
        if (! ctype_xdigit($key) || strlen($key) !== 64) {
            throw new \RuntimeException('privacy.indexKey invalida (se esperan 64 hex = 32 bytes).');
        }
        $this->indexKey = hex2bin($key);
    }

    /**
     * @param 'documento'|'email'|'texto' $type
     */
    public function blindIndex(?string $value, string $type = 'texto'): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        return hash_hmac('sha256', $this->normalize($value, $type), $this->indexKey);
    }

    private function normalize(string $value, string $type): string
    {
        return match ($type) {
            'documento' => preg_replace('/\D+/', '', $value) ?: '',
            'email' => mb_strtolower(trim($value)),
            default => mb_strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value)),
        };
    }
}
