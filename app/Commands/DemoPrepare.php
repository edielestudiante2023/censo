<?php

namespace App\Commands;

use App\Libraries\ClientInstrumentAccess;
use App\Libraries\PrivacyAccessGate;
use App\Libraries\PrivacyDocumentService;
use App\Libraries\PrivacyProgramService;
use App\Models\DpConsentimientoModel;
use App\Models\DpProgramaModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

final class DemoPrepare extends BaseCommand
{
    protected $group = 'Demo';
    protected $name = 'demo:prepare';
    protected $description = 'Completa de forma idempotente el cliente demo para presentaciones.';

    public function run(array $params)
    {
        $slug = trim((string) ($params[0] ?? 'demo-muestra'));
        $db = db_connect();
        $cliente = $db->table('clientes')->where('slug', $slug)->where('deleted_at', null)->get()->getRowArray();
        if (! $cliente) {
            CLI::error('No existe el cliente demo con slug ' . $slug . '. Ejecuta primero php spark demo:seed.');
            return EXIT_ERROR;
        }
        $clienteId = (int) $cliente['id'];
        $now = date('Y-m-d H:i:s');

        $clientRole = $db->table('roles')->select('id')->where('nombre', 'cliente')->get()->getRowArray();
        $demoUser = $clientRole ? $db->table('usuarios')->where('cliente_id', $clienteId)
            ->where('rol_id', $clientRole['id'])->where('deleted_at', null)->get()->getRowArray() : null;
        if ($demoUser) {
            $db->table('usuarios')->where('id', $demoUser['id'])->update([
                'email' => 'sistemasdegestionpropiedadhori@gmail.com',
                'password_hash' => password_hash('Demo2026*', PASSWORD_DEFAULT),
                'activo' => 1,
                'updated_at' => $now,
            ]);
        }

        $access = new ClientInstrumentAccess();
        foreach (array_keys(ClientInstrumentAccess::LABELS) as $instrument) {
            $access->set($clienteId, $instrument, true, null, 'Entorno demostrativo integral autorizado por Cycloid');
        }

        $programModel = new DpProgramaModel();
        $program = $programModel->where('cliente_id', $clienteId)->first();
        $config = array_replace(json_decode((string) ($program['config_json'] ?? '{}'), true) ?: [], [
            'demo_environment' => true,
            'area_responsable' => 'Administracion de la copropiedad',
            'horario_atencion' => 'Lunes a viernes de 8:00 a.m. a 5:00 p.m.',
            'organo_aprobacion' => 'Consejo de Administracion - Acta DEMO-001',
            'fecha_aprobacion' => date('Y-m-d'),
            'fecha_vigencia' => date('Y-m-d'),
            'medio_publicacion' => 'Portal de privacidad, cartelera y correo institucional',
            'url_politica' => base_url('privacidad/demo-politica'),
            'privacy_app_name' => 'Programa de Proteccion de Datos Personales',
            'request_cutoff_time' => '17:00',
            'request_file_years' => 5,
            'backup_rotation_days' => 90,
            'security_acta' => 'Acta DEMO-001',
            'security_administrador' => 'Administracion Demo',
            'security_opd_designacion' => 'Responsable operativo de privacidad',
            'security_opd_reporte' => 'Consejo de Administracion',
            'security_canal_incidentes' => 'privacidad.demo@example.test',
            'security_sedes' => 'Administracion y porteria',
            'security_archivo_ubicacion' => 'Archivo administrativo de acceso restringido',
            'security_archivo_custodio' => 'Administracion Demo',
            'security_gestor_secretos' => 'Gestor institucional',
            'security_soportes_fisicos' => 1,
            'encargados_publicos' => 'Proveedor de correo transaccional y proveedor de alojamiento, bajo contrato',
            'canal_entrega_copia' => 'Correo electronico verificado por el Titular',
        ]);
        $programData = [
            'responsable_nombre' => $cliente['nombre_tercero'],
            'responsable_documento' => $cliente['documento'] ?: '901456789-0',
            'responsable_direccion' => $cliente['direccion'] ?: 'Direccion institucional demostrativa',
            'responsable_ciudad' => $cliente['ciudad'] ?: 'Bogota D.C.',
            'canal_email' => 'privacidad.demo@example.test',
            'canal_telefono' => $cliente['telefono'] ?: '6010000000',
            'oficial_nombre' => 'Administracion Demo',
            'oficial_cargo' => 'Responsable operativo de privacidad',
            'estado' => 'activo',
            'config_json' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
        if ($program) {
            $programModel->update((int) $program['id'], $programData);
        } else {
            $programData += ['cliente_id' => $clienteId, 'public_token' => bin2hex(random_bytes(24))];
            $programModel->insert($programData);
        }

        $program = (new PrivacyProgramService())->initialize($cliente);
        $program = $programModel->find((int) $program['id']);
        $bases = $db->table('dp_bases_datos')->where('cliente_id', $clienteId)->where('activo', 1)->get()->getResultArray();
        $purposes = $db->table('dp_finalidades')->where('cliente_id', $clienteId)->where('activo', 1)->get()->getResultArray();
        $documents = $db->table('dp_documentos')->where('cliente_id', $clienteId)->orderBy('version', 'DESC')->get()->getResultArray();
        $latest = [];
        foreach ($documents as $document) {
            $latest[$document['tipo']] ??= $document;
        }
        $documentService = new PrivacyDocumentService();
        foreach ($latest as $document) {
            if ($document['estado'] === 'publicado') {
                continue;
            }
            $html = $documentService->render((string) $document['tipo'], $cliente, $program, $bases, $purposes);
            $db->table('dp_documentos')->where('id', $document['id'])->update([
                'contenido_html' => $html,
                'hash_sha256' => hash('sha256', $html),
                'estado' => 'publicado',
                'aprobado_at' => $now,
                'vigente_desde' => date('Y-m-d'),
                'publicado_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $db->table('dp_aviso_variantes')->where('cliente_id', $clienteId)->update(['estado' => 'publicado', 'publicado_at' => $now, 'updated_at' => $now]);

        $authorization = $db->table('dp_documentos')->where('cliente_id', $clienteId)->where('tipo', 'autorizacion')
            ->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
        $units = $db->table('inmuebles')->where('cliente_id', $clienteId)->where('deleted_at', null)->orderBy('id')->limit(3)->get()->getResultArray();
        if ($authorization && $units && $db->table('dp_consentimientos')->where('cliente_id', $clienteId)->where('canal', 'demo_preparado')->countAllResults() === 0) {
            $purposeIds = array_map('intval', array_column($purposes, 'id'));
            foreach (['autorizado', 'parcial', 'negado'] as $index => $decision) {
                if (! isset($units[$index])) {
                    break;
                }
                $accepted = $decision === 'negado' ? [] : ($decision === 'parcial' ? array_slice($purposeIds, 0, 2) : $purposeIds);
                $rejected = array_values(array_diff($purposeIds, $accepted));
                $instance = '<article><h1>Instancia demostrativa de autorizacion</h1><p>Persona ficticia ' . ($index + 1) . '</p>' . $authorization['contenido_html'] . '</article>';
                $instanceHash = hash('sha256', $instance);
                (new DpConsentimientoModel())->insert([
                    'cliente_id' => $clienteId,
                    'inmueble_id' => $units[$index]['id'],
                    'documento_id' => $authorization['id'],
                    'documento_version' => $authorization['version'],
                    'documento_hash' => $authorization['hash_sha256'],
                    'instancia_html' => $instance,
                    'instancia_hash' => $instanceHash,
                    'tipo_titular' => 'residente',
                    'titular_nombre' => 'Persona Ficticia Demo ' . ($index + 1),
                    'titular_tipo_documento' => 'CC',
                    'titular_documento' => '90000000' . ($index + 1),
                    'titular_email' => 'titular' . ($index + 1) . '@example.test',
                    'calidad_otorgante' => 'titular',
                    'decision' => $decision,
                    'decision_vector_json' => json_encode(['demo' => true, 'decision' => $decision]),
                    'finalidades_aceptadas_json' => json_encode($accepted),
                    'finalidades_rechazadas_json' => json_encode($rejected),
                    'evidencia_hash' => hash('sha256', $instanceHash . '|' . $decision),
                    'ip' => '127.0.0.1',
                    'user_agent' => 'Demo preflight',
                    'canal' => 'demo_preparado',
                    'tipo_evidencia' => 'escenario_demostrativo',
                    'verificacion_identidad' => 'Identidad ficticia controlada',
                    'zona_horaria' => 'America/Bogota',
                    'otorgado_at' => $now,
                ]);
            }
        }

        CLI::write('Demo preparado en la base activa: ' . $cliente['nombre_tercero'] . ' (id ' . $clienteId . ')', 'green');
        return $this->preflight($clienteId);
    }

    private function preflight(int $clienteId): int
    {
        $db = db_connect();
        $demoUser = $db->table('usuarios')->where('cliente_id', $clienteId)
            ->where('email', 'sistemasdegestionpropiedadhori@gmail.com')->where('deleted_at', null)->get()->getRowArray();
        $checks = [
            '3 instrumentos habilitados' => count(array_filter((new ClientInstrumentAccess())->enabledMap($clienteId))) === 3,
            'unidades habitacionales' => $db->table('inmuebles')->where('cliente_id', $clienteId)->where('deleted_at', null)->countAllResults() >= 1,
            'programa activo' => $db->table('dp_programas')->where('cliente_id', $clienteId)->where('estado', 'activo')->countAllResults() === 1,
            'inventario de bases' => $db->table('dp_bases_datos')->where('cliente_id', $clienteId)->where('activo', 1)->countAllResults() >= 5,
            '7 documentos publicados' => $db->table('dp_documentos')->where('cliente_id', $clienteId)->where('estado', 'publicado')->countAllResults() >= 7,
            'decisiones demostrativas' => $db->table('dp_consentimientos')->where('cliente_id', $clienteId)->where('canal', 'demo_preparado')->countAllResults() >= 3,
            'correo real del usuario cliente' => $db->table('usuarios')->where('cliente_id', $clienteId)
                ->where('email', 'sistemasdegestionpropiedadhori@gmail.com')->where('activo', 1)->countAllResults() === 1,
            'credenciales del usuario demo' => $demoUser && password_verify('Demo2026*', (string) $demoUser['password_hash']),
            'acceso del usuario demo' => $demoUser && (new PrivacyAccessGate())->ready($clienteId, (int) $demoUser['id']),
        ];
        foreach ($checks as $label => $ok) {
            CLI::write(($ok ? '[OK] ' : '[FALTA] ') . $label, $ok ? 'green' : 'red');
        }
        if (in_array(false, $checks, true)) {
            CLI::error('DEMO NO LISTO');
            return EXIT_ERROR;
        }
        CLI::write('DEMO LISTO', 'green');
        return EXIT_SUCCESS;
    }
}
