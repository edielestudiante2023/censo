<?php

namespace App\Libraries;

class PrivacyAccessGate
{
    public function required(int $clientId): bool
    {
        $db = db_connect();
        if (! $db->tableExists('dp_documentos') || ! $db->tableExists('dp_compromisos_confidencialidad')) {
            return false;
        }

        return $db->table('dp_documentos')->where('cliente_id', $clientId)->where('tipo', 'confidencialidad')
            ->where('estado', 'publicado')->countAllResults() > 0;
    }

    public function ready(int $clientId, int $userId): bool
    {
        if (! $this->required($clientId)) {
            return true;
        }

        $db = db_connect();
        $master = $db->table('dp_documentos')->where('cliente_id', $clientId)->where('tipo', 'confidencialidad')
            ->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
        $agreement = $db->table('dp_compromisos_confidencialidad')->where('cliente_id', $clientId)->where('usuario_id', $userId)
            ->where('estado', 'vigente')->where('vigencia_desde <=', date('Y-m-d'))->where('vigencia_hasta >=', date('Y-m-d'))
            ->orderBy('aceptado_at', 'DESC')->get()->getRowArray();
        if ($agreement) {
            $agreement = (new PrivacyVault())->decryptRow('dp_compromisos_confidencialidad', $agreement);
        }
        $compliance = $db->table('dp_usuario_privacidad')->where('cliente_id', $clientId)->where('usuario_id', $userId)->get()->getRowArray();

        if (! $master || ! $agreement || ! $compliance || empty($compliance['induccion_at']) || empty($agreement['aceptado_at'])) {
            $this->suspend($clientId, $userId);
            return false;
        }
        if (! hash_equals((string) $master['hash_sha256'], hash('sha256', (string) $master['contenido_html']))) {
            $this->suspend($clientId, $userId);
            return false;
        }
        if ((int) $agreement['documento_id'] !== (int) $master['id']
            || ! hash_equals((string) $agreement['documento_hash'], (string) $master['hash_sha256'])
            || ! hash_equals((string) ($compliance['confidencialidad_hash'] ?? ''), (string) $agreement['instancia_hash'])) {
            $this->suspend($clientId, $userId);
            return false;
        }

        $valid = (new PrivacyConfidentialityService())->verify((string) $agreement['instancia_html'], (string) $agreement['instancia_hash']);
        if (! $valid) {
            $this->suspend($clientId, $userId);
        }
        return $valid;
    }

    public function requiresMfa(int $clientId, int $userId): bool
    {
        $agreement = $this->currentAgreement($clientId, $userId);
        if (! $agreement) {
            return false;
        }
        $criticalRoles = ['administracion', 'contabilidad', 'auditoria', 'soporte_ti', 'oficial_proteccion_datos'];
        $criticalOperations = ['exportar', 'suprimir', 'visualizar_cctv', 'operar_biometria'];
        $operations = json_decode((string) $agreement['operaciones_json'], true) ?: [];
        return in_array($agreement['rol'], $criticalRoles, true) || array_intersect($operations, $criticalOperations) !== [];
    }

    public function allowsOperation(int $clientId, int $userId, string $operation): bool
    {
        if (! $this->required($clientId)) {
            return true;
        }
        $agreement = $this->currentAgreement($clientId, $userId);
        $operations = $agreement ? (json_decode((string) $agreement['operaciones_json'], true) ?: []) : [];
        return in_array($operation, $operations, true);
    }

    public function allowsBase(int $clientId, int $userId, int $baseId): bool
    {
        if (! $this->required($clientId)) {
            return true;
        }
        $agreement = $this->currentAgreement($clientId, $userId);
        $bases = $agreement ? array_map('intval', json_decode((string) $agreement['bases_json'], true) ?: []) : [];
        return in_array($baseId, $bases, true);
    }

    public function allowsPrivacyGovernance(int $clientId, int $userId): bool
    {
        if (! $this->required($clientId)) {
            return true;
        }
        $agreement = $this->currentAgreement($clientId, $userId);
        return $agreement && in_array($agreement['rol'], ['administracion', 'oficial_proteccion_datos'], true);
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

    private function suspend(int $clientId, int $userId): void
    {
        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $db->table('usuarios')->where('id', $userId)->where('cliente_id', $clientId)->update(['activo' => 0, 'updated_at' => $now]);
        if ($db->tableExists('dp_usuario_privacidad')) {
            $db->table('dp_usuario_privacidad')->where('cliente_id', $clientId)->where('usuario_id', $userId)->update(['suspendido_at' => $now, 'updated_at' => $now]);
        }
        $expired = $db->table('dp_compromisos_confidencialidad')->where('cliente_id', $clientId)->where('usuario_id', $userId)
            ->where('estado', 'vigente')->where('vigencia_hasta <', date('Y-m-d'))->get()->getResultArray();
        foreach ($expired as $agreement) {
            $db->table('dp_compromisos_confidencialidad')->where('id', $agreement['id'])->update((new PrivacyVault())->encryptRow('dp_compromisos_confidencialidad', ['estado' => 'vencido', 'cerrado_at' => $now,
                'cierre_motivo' => 'Vencimiento automatico de la vigencia', 'updated_at' => $now]));
        }
    }
}
