<?php

namespace App\Libraries;

final class PrivacyConsentGate
{
    public function allows(int $clienteId, string $identifier, string|int $purposeKey): bool
    {
        $db = db_connect();
        $identifier = trim($identifier);
        $vault = new PrivacyVault();
        $newHash = (new PrivacyPii())->blindIndex($identifier, 'documento');
        $legacyHash = $this->identifierHash($identifier);
        $exclusion = $db->table('dp_exclusiones')->where('cliente_id', $clienteId)->where('activo', 1)
            ->groupStart()->where('identificador_hash', $newHash)->orWhere('identificador_hash', $legacyHash)->groupEnd()->get()->getRowArray();
        if ($exclusion) {
            if ($exclusion['alcance'] === 'total') {
                return false;
            }
            $blocked = json_decode((string) ($exclusion['finalidades_json'] ?? '[]'), true) ?: [];
            if (in_array((string) $purposeKey, array_map('strval', $blocked), true)) {
                return false;
            }
        }

        $builder = $db->table('dp_consentimientos')->where('cliente_id', $clienteId)->groupStart()
            ->where('titular_documento_bidx', $vault->blindLookup('dp_consentimientos', 'titular_documento', $identifier));
        if (config(\Config\PrivacyEncryption::class)->blindIndexFallback) {
            $builder->orWhere('titular_documento', $identifier);
        }
        $consent = $builder->groupEnd()->orderBy('otorgado_at', 'DESC')->get()->getRowArray();
        if (! $consent) {
            return false;
        }
        $consent = $vault->decryptRow('dp_consentimientos', $consent);
        $events = $vault->decryptRows('dp_consentimiento_eventos', $db->table('dp_consentimiento_eventos')->where('consentimiento_id', $consent['id'])
            ->where('tipo', 'revocatoria')->orderBy('ocurrido_at', 'DESC')->get()->getResultArray());
        foreach ($events as $event) {
            $scope = json_decode((string) $event['alcance_json'], true) ?: [];
            if (($scope['resultado'] ?? '') === 'total') {
                return false;
            }
            if (in_array((string) $purposeKey, array_map('strval', (array) ($scope['finalidades'] ?? [])), true)) {
                return false;
            }
        }
        $vector = json_decode((string) ($consent['decision_vector_json'] ?? '{}'), true) ?: [];
        $record = $vector[(string) $purposeKey] ?? null;
        $decision = is_array($record) ? ($record['decision'] ?? null) : $record;
        return $decision === 'autoriza';
    }

    private function identifierHash(string $identifier): string
    {
        $key = (string) (config('Encryption')->key ?? env('encryption.key') ?? 'dp-local');
        return hash_hmac('sha256', mb_strtolower($identifier), $key);
    }
}
