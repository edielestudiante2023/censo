<?php

namespace App\Database\Migrations;

use App\Libraries\PrivacyDocumentService;
use CodeIgniter\Database\Migration;

class RefreshPrivacySecurityManual extends Migration
{
    public function up(): void
    {
        $documents = $this->db->table('dp_documentos')->where('tipo', 'seguridad')
            ->whereIn('estado', ['borrador', 'en_revision'])->get()->getResultArray();
        $service = new PrivacyDocumentService();
        foreach ($documents as $document) {
            $client = $this->db->table('clientes')->where('id', $document['cliente_id'])->get()->getRowArray();
            $program = $this->db->table('dp_programas')->where('cliente_id', $document['cliente_id'])->get()->getRowArray();
            if (! $client || ! $program) {
                continue;
            }
            $bases = $this->db->table('dp_bases_datos')->where('cliente_id', $document['cliente_id'])->where('activo', 1)->get()->getResultArray();
            $purposes = $this->db->table('dp_finalidades')->where('cliente_id', $document['cliente_id'])->where('activo', 1)->get()->getResultArray();
            $versions = [];
            $dependencies = [];
            foreach (['politica', 'aviso', 'autorizacion', 'procedimiento'] as $type) {
                $dependency = $this->db->table('dp_documentos')->select('version, hash_sha256')->where('cliente_id', $document['cliente_id'])
                    ->where('tipo', $type)->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
                $versions[$type] = $dependency ? (int) $dependency['version'] : 'pendiente';
                $dependencies[$type] = $dependency ? ['version' => (int) $dependency['version'], 'hash' => $dependency['hash_sha256']] : null;
            }
            $thirdParties = $this->db->table('dp_terceros')->where('cliente_id', $document['cliente_id'])->where('activo', 1)->orderBy('nombre')->get()->getResultArray();
            $content = $service->render('seguridad', $client, $program, $bases, $purposes,
                ['third_parties' => $thirdParties, 'document_versions' => $versions]);
            $variables = json_decode((string) ($document['variables_json'] ?? '{}'), true) ?: [];
            $variables['security_schema_version'] = 2;
            $variables['document_dependencies'] = $dependencies;
            $variables['refreshed_at'] = date('Y-m-d H:i:s');
            $this->db->table('dp_documentos')->where('id', $document['id'])->update([
                'contenido_html' => $content, 'variables_json' => json_encode($variables, JSON_UNESCAPED_UNICODE),
                'hash_sha256' => hash('sha256', $content), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        // Los textos juridicos versionados no se degradan automaticamente.
    }
}
