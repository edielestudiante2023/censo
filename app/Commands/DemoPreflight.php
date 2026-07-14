<?php

namespace App\Commands;

use App\Libraries\ClientInstrumentAccess;
use App\Libraries\PrivacyAccessGate;
use App\Models\DpDocumentoModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

final class DemoPreflight extends BaseCommand
{
    protected $group = 'Demo';
    protected $name = 'demo:preflight';
    protected $description = 'Verifica que el cliente demo de produccion este listo para una reunion.';

    public function run(array $params)
    {
        $slug = trim((string) ($params[0] ?? 'demo-muestra'));
        $db = db_connect();
        $client = $db->table('clientes')->where('slug', $slug)->where('deleted_at', null)->get()->getRowArray();
        if (! $client) {
            CLI::error('DEMO NO LISTO: no existe el cliente ' . $slug);
            return EXIT_ERROR;
        }
        $id = (int) $client['id'];
        $demoUser = $db->table('usuarios')->where('cliente_id', $id)
            ->where('email', 'sistemasdegestionpropiedadhori@gmail.com')->where('deleted_at', null)->get()->getRowArray();
        $checks = [
            'instrumentos' => count(array_filter((new ClientInstrumentAccess())->enabledMap($id))) === 3,
            'unidades' => $db->table('inmuebles')->where('cliente_id', $id)->where('deleted_at', null)->countAllResults() >= 1,
            'programa' => $db->table('dp_programas')->where('cliente_id', $id)->where('estado', 'activo')->countAllResults() === 1,
            'bases' => $db->table('dp_bases_datos')->where('cliente_id', $id)->where('activo', 1)->countAllResults() >= 5,
            'documentos' => $db->table('dp_documentos')->where('cliente_id', $id)->where('estado', 'publicado')->countAllResults() >= 7,
            'documentos de acceso versionados' => $this->accessDocumentsReady($id),
            'decisiones' => $db->table('dp_consentimientos')->where('cliente_id', $id)->where('canal', 'demo_preparado')->countAllResults() >= 3,
            'usuario cliente con correo real' => $db->table('usuarios')->where('cliente_id', $id)
                ->where('email', 'sistemasdegestionpropiedadhori@gmail.com')->where('activo', 1)->countAllResults() === 1,
            'credenciales del usuario demo' => $demoUser && password_verify('Demo2026*', (string) $demoUser['password_hash']),
            'acceso del usuario demo' => $demoUser && (new PrivacyAccessGate())->ready($id, (int) $demoUser['id']),
        ];
        foreach ($checks as $name => $ok) {
            CLI::write(($ok ? '[OK] ' : '[FALTA] ') . $name, $ok ? 'green' : 'red');
        }
        if (in_array(false, $checks, true)) {
            CLI::error('DEMO NO LISTO');
            return EXIT_ERROR;
        }
        CLI::write('DEMO LISTO: ' . $client['nombre_tercero'] . ' (id ' . $id . ')', 'green');
        return EXIT_SUCCESS;
    }

    private function accessDocumentsReady(int $clienteId): bool
    {
        $model = new DpDocumentoModel();
        foreach (['seguridad', 'confidencialidad', 'encargados'] as $type) {
            $document = $model->where('cliente_id', $clienteId)->where('tipo', $type)
                ->where('estado', 'publicado')->orderBy('version', 'DESC')->first();
            if (! $document) {
                return false;
            }
            $variables = json_decode((string) ($document['variables_json'] ?? '{}'), true) ?: [];
            if ((int) ($variables['demo_access_schema_version'] ?? 0) < 1
                || ! hash_equals((string) $document['hash_sha256'], hash('sha256', (string) $document['contenido_html']))) {
                return false;
            }
        }
        return true;
    }
}
