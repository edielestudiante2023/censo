<?php

namespace App\Libraries;

use Config\PrivacyEncryption;

/**
 * M-1: punto unico de cifrado/descifrado de filas del modulo de proteccion de datos.
 * Lo usan tanto los modelos Dp* (via trait) como los accesos por Query Builder directo
 * y el comando de migracion, garantizando una sola politica.
 */
final class PrivacyVault
{
    private PrivacyCipher $cipher;
    private PrivacyPii $pii;
    private PrivacyEncryption $config;

    public function __construct(?PrivacyCipher $cipher = null, ?PrivacyPii $pii = null, ?PrivacyEncryption $config = null)
    {
        $this->cipher = $cipher ?? new PrivacyCipher();
        $this->pii = $pii ?? new PrivacyPii();
        $this->config = $config ?? config(PrivacyEncryption::class);
    }

    public function handles(string $table): bool
    {
        return isset($this->config->tables[$table]);
    }

    /**
     * Cifra los campos declarados de una fila y calcula sus indices ciegos a partir
     * del texto plano (antes de cifrar). Solo toca claves presentes en $row.
     */
    public function encryptRow(string $table, array $row): array
    {
        // 1) Indices ciegos desde el texto plano (solo si la columna origen viene en la fila).
        foreach ($this->config->blindFields($table) as $source => $spec) {
            if (array_key_exists($source, $row)) {
                $row[$spec['col']] = $this->pii->blindIndex($this->plain($table, $source, $row[$source]), $spec['type']);
            }
        }
        // 2) Cifrado de los campos declarados.
        foreach ($this->config->encryptFields($table) as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $row[$field] = $this->cipher->encrypt((string) $row[$field], $this->aad($table, $field));
            }
        }
        return $row;
    }

    /**
     * Descifra los campos declarados de una fila. Si un valor tiene prefijo de
     * ciphertext y la verificacion AEAD falla, PrivacyCipher lanza excepcion: NUNCA
     * se degrada a texto plano.
     */
    public function decryptRow(string $table, array $row): array
    {
        foreach ($this->config->encryptFields($table) as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $row[$field] = $this->cipher->decrypt((string) $row[$field], $this->aad($table, $field));
            }
        }
        return $row;
    }

    /**
     * @param list<array> $rows
     * @return list<array>
     */
    public function decryptRows(string $table, array $rows): array
    {
        foreach ($rows as &$row) {
            $row = $this->decryptRow($table, $row);
        }
        return $rows;
    }

    /**
     * Valor de indice ciego para buscar por una columna origen (busquedas por igualdad).
     */
    public function blindLookup(string $table, string $sourceField, string $value): ?string
    {
        $spec = $this->config->blindFields($table)[$sourceField] ?? null;
        return $spec ? $this->pii->blindIndex($value, $spec['type']) : null;
    }

    public function encryptValue(string $table, string $field, ?string $value): ?string
    {
        return $this->cipher->encrypt($value, $this->aad($table, $field));
    }

    public function decryptValue(string $table, string $field, ?string $value): ?string
    {
        return $this->cipher->decrypt($value, $this->aad($table, $field));
    }

    public function encryptFile(string $binary, string $context): string
    {
        return $this->cipher->encryptBinary($binary, 'censo-privacy-file|' . $context);
    }

    public function decryptFile(string $blob, string $context): string
    {
        return $this->cipher->decryptBinary($blob, 'censo-privacy-file|' . $context);
    }

    /**
     * Verifica que el hash probatorio de una fila (ya en texto plano) siga siendo
     * valido. Sirve para la verificacion antes/despues de la migracion y al leer.
     */
    public function verifyHashInvariant(string $table, array $plainRow): bool
    {
        $spec = $this->config->hashInvariants[$table] ?? null;
        if (! $spec) {
            return true;
        }
        $html = (string) ($plainRow[$spec['plain']] ?? '');
        $hash = (string) ($plainRow[$spec['hash']] ?? '');
        if ($html === '' || $hash === '') {
            return true; // sin instancia sellada aun (p.ej. pendiente_firma)
        }
        return match ($table) {
            'dp_consentimientos' => hash_equals($hash, hash('sha256', $html)),
            'dp_compromisos_confidencialidad' => (new PrivacyConfidentialityService())->verify($html, $hash),
            'dp_acuerdos_encargado' => (new PrivacyProcessorAgreementService())->verify($html, $hash),
            default => true,
        };
    }

    public function cipher(): PrivacyCipher
    {
        return $this->cipher;
    }

    public function isEncrypted(?string $value): bool
    {
        return $this->cipher->isEncrypted($value);
    }

    private function plain(string $table, string $field, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = (string) $value;
        return $this->cipher->isEncrypted($value) ? $this->cipher->decrypt($value, $this->aad($table, $field)) : $value;
    }

    private function aad(string $table, string $field): string
    {
        return 'censo-privacy|' . $table . '|' . $field;
    }
}
