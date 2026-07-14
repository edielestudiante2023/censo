<?php

namespace App\Libraries;

final class PrivacyMfaPolicy
{
    public function requiresMfa(int $clientId, int $userId): bool
    {
        $agreement = $this->currentAgreement($clientId, $userId);
        if (! $agreement) {
            return false;
        }

        $criticalRoles = ['administracion', 'contabilidad', 'auditoria', 'soporte_ti', 'oficial_proteccion_datos'];
        $criticalOperations = ['exportar', 'suprimir', 'visualizar_cctv', 'operar_biometria'];
        $operations = json_decode((string) $agreement['operaciones_json'], true) ?: [];

        return in_array($agreement['rol'], $criticalRoles, true)
            || array_intersect($operations, $criticalOperations) !== [];
    }

    private function currentAgreement(int $clientId, int $userId): ?array
    {
        $db = db_connect();
        if (! $db->tableExists('dp_compromisos_confidencialidad')) {
            return null;
        }
        $agreement = $db->table('dp_compromisos_confidencialidad')->where('cliente_id', $clientId)->where('usuario_id', $userId)
            ->where('estado', 'vigente')->where('vigencia_desde <=', date('Y-m-d'))->where('vigencia_hasta >=', date('Y-m-d'))
            ->orderBy('aceptado_at', 'DESC')->get()->getRowArray() ?: null;

        return $agreement ? (new PrivacyVault())->decryptRow('dp_compromisos_confidencialidad', $agreement) : null;
    }
}
