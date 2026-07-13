<?php

namespace App\Libraries;

final class PrivacyRestoreGuard
{
    public function __construct(private readonly ?string $hashKey = null)
    {
    }

    public function identifierHash(string $identifier): string
    {
        if ($this->hashKey === null) {
            return (new PrivacyPii())->blindIndex($identifier, 'documento');
        }
        return hash_hmac('sha256', mb_strtolower(trim($identifier)), $this->hashKey);
    }

    /**
     * @return array{accepted: array, blocked: array, exclusion_version_hash: string}
     */
    public function partition(array $rows, callable $identifier, array $activeExclusionHashes): array
    {
        $activeExclusionHashes = array_values(array_unique(array_filter($activeExclusionHashes)));
        sort($activeExclusionHashes);
        $lookup = array_fill_keys($activeExclusionHashes, true);
        $accepted = [];
        $blocked = [];
        foreach ($rows as $row) {
            $hash = $this->identifierHash((string) $identifier($row));
            if (isset($lookup[$hash])) {
                $blocked[] = ['row' => $row, 'identifier_hash' => $hash];
            } else {
                $accepted[] = $row;
            }
        }
        return ['accepted' => $accepted, 'blocked' => $blocked,
            'exclusion_version_hash' => hash('sha256', implode('|', $activeExclusionHashes))];
    }

    public function record(int $clientId, string $backup, array $result, ?int $userId = null): int
    {
        $now = date('Y-m-d H:i:s');
        $db = db_connect();
        $db->table('dp_restauraciones_privacidad')->insert([
            'cliente_id' => $clientId, 'respaldo' => $backup,
            'lista_version_hash' => $result['exclusion_version_hash'],
            'registros_filtrados' => count($result['blocked']),
            'verificacion_estado' => $result['blocked'] === [] ? 'sin_coincidencias' : 'filtrada',
            'detalle' => 'La lista de exclusiones se aplico antes de habilitar los datos restaurados.',
            'ejecutado_por' => $userId, 'ejecutado_at' => $now, 'created_at' => $now,
        ]);
        return (int) $db->insertID();
    }

    public function recordReappearanceIncident(int $clientId, string $identifierHash, string $detail): void
    {
        $now = date('Y-m-d H:i:s');
        $row = [
            'cliente_id' => $clientId, 'tipo' => 'reaparicion_dato_suprimido',
            'identificador_hash' => $identifierHash, 'detalle' => $detail, 'estado' => 'abierto',
            'detectado_at' => $now, 'conocimiento_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ];
        db_connect()->table('dp_incidentes_privacidad')->insert((new PrivacyVault())->encryptRow('dp_incidentes_privacidad', $row));
    }
}
