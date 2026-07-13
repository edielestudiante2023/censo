<?php

namespace App\Libraries;

final class PrivacyProcessorGate
{
    public function required(int $clientId): bool
    {
        $db = db_connect();
        return $db->tableExists('dp_acuerdos_encargado') && $db->table('dp_documentos')->where('cliente_id', $clientId)
            ->where('tipo', 'encargados')->where('estado', 'publicado')->countAllResults() > 0;
    }

    /**
     * A-1: fail-closed. Un proveedor externo (SendGrid, OpenAI) solo puede recibir
     * datos si existe un tercero habilitado, clasificado y con un Acuerdo (Documento 7)
     * vigente, integro y con doble firma verificada. La ausencia de programa de
     * encargados NO abre el canal: sin acuerdo valido, la transmision se bloquea.
     * Los correos operativos estrictamente necesarios (MFA, restablecimiento) usan
     * el canal de arranque de EmailService y no pasan por esta compuerta.
     */
    public function allowsProvider(int $clientId, string $provider): bool
    {
        $db = db_connect();
        if (! $db->tableExists('dp_terceros') || ! $db->tableExists('dp_acuerdos_encargado')) {
            return false;
        }
        $provider = mb_strtolower(trim($provider));
        if ($provider === '') {
            return false;
        }
        $vault = new PrivacyVault();
        $thirds = $vault->decryptRows('dp_terceros', $db->table('dp_terceros')->where('cliente_id', $clientId)->where('habilitado_datos', 1)->where('activo', 1)->get()->getResultArray());
        foreach ($thirds as $third) {
            $direct = $this->nameMatchesProvider((string) $third['nombre'], $provider);
            $sub = (bool) array_filter(
                $vault->decryptRows('dp_subencargados', $db->table('dp_subencargados')->where('cliente_id', $clientId)->where('tercero_id', $third['id'])->where('activo', 1)->get()->getResultArray()),
                fn (array $row): bool => $this->nameMatchesProvider((string) $row['nombre'], $provider)
            );
            if (! $direct && ! $sub) {
                continue;
            }
            if ($this->validAgreement($clientId, (int) $third['id'])) {
                return true;
            }
            // Coincide con el proveedor pero su acuerdo dejo de ser valido: se revoca la habilitacion.
            $db->table('dp_terceros')->where('id', $third['id'])->update(['habilitado_datos' => 0, 'contrato_vigente' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        }
        return false;
    }

    /**
     * El proveedor canonico ('sendgrid', 'openai') debe aparecer como token del
     * nombre registrado, evitando falsos positivos por subcadenas arbitrarias.
     */
    private function nameMatchesProvider(string $name, string $provider): bool
    {
        $tokens = preg_split('/[^a-z0-9]+/', mb_strtolower($name)) ?: [];
        return in_array($provider, $tokens, true);
    }

    private function validAgreement(int $clientId, int $thirdId): bool
    {
        $db = db_connect();
        $agreement = $db->table('dp_acuerdos_encargado')->where('cliente_id', $clientId)->where('tercero_id', $thirdId)
            ->where('estado', 'vigente')->where('vigencia_desde <=', date('Y-m-d'))->where('vigencia_hasta >=', date('Y-m-d'))
            ->orderBy('encargado_firmado_at', 'DESC')->get()->getRowArray();
        if ($agreement) {
            $agreement = (new PrivacyVault())->decryptRow('dp_acuerdos_encargado', $agreement);
        }
        $master = $db->table('dp_documentos')->where('cliente_id', $clientId)->where('tipo', 'encargados')->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
        return $agreement && $master && ! empty($agreement['responsable_firma']) && ! empty($agreement['responsable_firmado_at'])
            && ! empty($agreement['encargado_firma']) && ! empty($agreement['encargado_firmado_at'])
            && hash_equals((string) $agreement['responsable_firma_hash'], hash('sha256', (string) $agreement['responsable_firma']))
            && hash_equals((string) $agreement['encargado_firma_hash'], hash('sha256', (string) $agreement['encargado_firma']))
            && (int) $agreement['documento_id'] === (int) $master['id']
            && hash_equals($master['hash_sha256'], hash('sha256', $master['contenido_html']))
            && hash_equals($agreement['documento_hash'], $master['hash_sha256'])
            && (new PrivacyProcessorAgreementService())->verify($agreement['instancia_html'], $agreement['instancia_hash']);
    }
}
