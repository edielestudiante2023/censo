<?php

namespace App\Database\Migrations;

use App\Libraries\PrivacyDocumentService;
use CodeIgniter\Database\Migration;

class RefreshPrivacyProcessorAgreementMaster extends Migration
{
    public function up(): void
    {
        foreach ($this->db->table('dp_documentos')->where('tipo', 'encargados')->whereIn('estado', ['borrador', 'en_revision'])->get()->getResultArray() as $document) {
            $client = $this->db->table('clientes')->where('id', $document['cliente_id'])->get()->getRowArray();
            $program = $this->db->table('dp_programas')->where('cliente_id', $document['cliente_id'])->get()->getRowArray();
            if (! $client || ! $program) { continue; }
            $dependencies = [];
            foreach (['politica', 'aviso', 'autorizacion', 'procedimiento', 'seguridad', 'confidencialidad'] as $type) {
                $doc = $this->db->table('dp_documentos')->where('cliente_id', $document['cliente_id'])->where('tipo', $type)->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
                $dependencies[$type] = $doc ? ['version' => (int) $doc['version'], 'hash' => $doc['hash_sha256']] : null;
            }
            $bases = $this->db->table('dp_bases_datos')->where('cliente_id', $document['cliente_id'])->where('activo', 1)->get()->getResultArray();
            $purposes = $this->db->table('dp_finalidades')->where('cliente_id', $document['cliente_id'])->where('activo', 1)->get()->getResultArray();
            $content = (new PrivacyDocumentService())->render('encargados', $client, $program, $bases, $purposes);
            $variables = json_decode((string) ($document['variables_json'] ?? '{}'), true) ?: [];
            $variables['processor_agreement_schema_version'] = 2; $variables['document_dependencies'] = $dependencies; $variables['refreshed_at'] = date('Y-m-d H:i:s');
            $this->db->table('dp_documentos')->where('id', $document['id'])->update(['contenido_html' => $content,
                'variables_json' => json_encode($variables, JSON_UNESCAPED_UNICODE), 'hash_sha256' => hash('sha256', $content), 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function down(): void
    {
        // Los textos juridicos versionados no se degradan automaticamente.
    }
}
