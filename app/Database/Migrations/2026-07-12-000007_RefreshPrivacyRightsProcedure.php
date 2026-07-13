<?php

namespace App\Database\Migrations;

use App\Libraries\PrivacyDocumentService;
use CodeIgniter\Database\Migration;

class RefreshPrivacyRightsProcedure extends Migration
{
    public function up(): void
    {
        $documents = $this->db->table('dp_documentos')->where('tipo', 'procedimiento')
            ->whereIn('estado', ['borrador', 'en_revision'])->get()->getResultArray();
        $service = new PrivacyDocumentService();
        foreach ($documents as $document) {
            $client = $this->db->table('clientes')->where('id', $document['cliente_id'])->get()->getRowArray();
            $program = $this->db->table('dp_programas')->where('cliente_id', $document['cliente_id'])->get()->getRowArray();
            if (! $client || ! $program) {
                continue;
            }
            $content = $service->render('procedimiento', $client, $program, [], []);
            $variables = json_decode((string) ($document['variables_json'] ?? '{}'), true) ?: [];
            $variables['workflow_schema_version'] = 2;
            $variables['refreshed_at'] = date('Y-m-d H:i:s');
            $this->db->table('dp_documentos')->where('id', $document['id'])->update([
                'titulo' => 'Procedimiento de Consultas, Reclamos, Rectificacion, Actualizacion, Revocatorias y Supresion',
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
