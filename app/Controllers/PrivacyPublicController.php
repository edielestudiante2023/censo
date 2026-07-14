<?php

namespace App\Controllers;

use App\Libraries\EmailService;
use App\Libraries\PrivacyAudit;
use App\Libraries\PrivacyBusinessDays;
use App\Libraries\PrivacyPdf;
use App\Libraries\PrivacyPii;
use App\Libraries\PrivacyRequestWorkflow;
use App\Libraries\PrivacyVault;
use App\Models\ClienteModel;
use App\Models\DpConsentimientoModel;
use App\Models\DpDocumentoModel;
use App\Models\DpNotificacionModel;
use App\Models\DpProgramaModel;
use App\Models\DpSolicitudModel;

class PrivacyPublicController extends BaseController
{
    private const HOUSING_HOLDER_TYPES = ['propietario', 'residente', 'arrendatario', 'menor de edad'];

    public function portal(string $token)
    {
        $context = $this->context($token);
        if (! $context) {
            return view('privacy/public_not_found');
        }
        return view('privacy/public_portal', $context + ['success' => false]);
    }

    public function sendConsentCode(string $token)
    {
        $context = $this->context($token);
        if (! $context || ! $context['authorization']) {
            return view('privacy/public_not_found');
        }
        $data = [
            'tipo_titular' => trim((string) $this->request->getPost('tipo_titular')),
            'inmueble_id' => (int) $this->request->getPost('inmueble_id') ?: null,
            'titular_nombre' => trim((string) $this->request->getPost('titular_nombre')),
            'titular_tipo_documento' => trim((string) $this->request->getPost('titular_tipo_documento')),
            'titular_documento' => trim((string) $this->request->getPost('titular_documento')),
            'titular_email' => mb_strtolower(trim((string) $this->request->getPost('titular_email'))),
            'calidad_otorgante' => (string) ($this->request->getPost('calidad_otorgante') ?: 'titular'),
            'representante_nombre' => $this->nullable('representante_nombre'),
            'representante_documento' => $this->nullable('representante_documento'),
            'calidad_representacion' => $this->nullable('calidad_representacion'),
            'opinion_menor' => $this->nullable('opinion_menor'),
        ];
        if (! $this->validateData($data, [
            'tipo_titular' => 'required|max_length[50]', 'titular_nombre' => 'required|max_length[191]',
            'titular_documento' => 'required|max_length[80]', 'titular_email' => 'required|valid_email|max_length[191]',
            'calidad_otorgante' => 'required|in_list[titular,representante_menor,apoderado]',
            'inmueble_id' => 'permit_empty|is_natural_no_zero',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $housingUnit = $data['inmueble_id']
            ? $this->findHousingUnit((int) $context['cliente']['id'], (int) $data['inmueble_id'])
            : null;
        if ($this->requiresHousingUnit($data['tipo_titular']) && ! $housingUnit) {
            return redirect()->back()->withInput()->with('error', 'Selecciona la torre y la unidad habitacional que corresponden al Titular.');
        }
        if (! $this->requiresHousingUnit($data['tipo_titular'])) {
            $housingUnit = null;
            $data['inmueble_id'] = null;
        }
        $data['inmueble_label'] = $housingUnit ? $this->housingUnitLabel($housingUnit) : null;
        if ($data['calidad_otorgante'] !== 'titular' && (! $data['representante_nombre'] || ! $data['representante_documento'] || ! $data['calidad_representacion'])) {
            return redirect()->back()->withInput()->with('error', 'Identifica al representante o apoderado y la calidad en que actua.');
        }
        // A-2: evita email bombing por el endpoint publico de envio de codigo.
        $throttler = service('throttler');
        if ($throttler->check('consent-code-ip-' . $this->request->getIPAddress(), 5, 10 * MINUTE) === false
            || $throttler->check('consent-code-dst-' . hash('sha256', $data['titular_email']), 5, HOUR) === false) {
            return redirect()->back()->withInput()->with('error', 'Se enviaron demasiados codigos. Espera unos minutos antes de solicitar otro.');
        }
        if ($data['calidad_otorgante'] === 'representante_menor' && ! $data['opinion_menor']) {
            return redirect()->back()->withInput()->with('error', 'Registra la constancia de escucha y valoracion de la opinion del menor.');
        }
        try {
            $support = $data['calidad_otorgante'] === 'titular' ? null : $this->saveRepresentationSupport((int) $context['cliente']['id']);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
        if ($data['calidad_otorgante'] !== 'titular' && ! $support) {
            return redirect()->back()->withInput()->with('error', 'Adjunta el registro civil, poder o soporte de la representacion.');
        }
        $supportPath = $support['path'] ?? null;
        $supportHash = $support['hash'] ?? null;
        $code = (string) random_int(100000, 999999);
        $now = date('Y-m-d H:i:s');
        $db = db_connect();
        $db->table('dp_consentimiento_verificaciones')->insert($this->vault()->encryptRow('dp_consentimiento_verificaciones', [
            'cliente_id' => $context['cliente']['id'], 'documento_hash' => $context['authorization']['hash_sha256'],
            'email' => $data['titular_email'], 'datos_json' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'soporte_representacion_ruta' => $supportPath, 'soporte_representacion_hash' => $supportHash,
            'codigo_hash' => password_hash($code, PASSWORD_DEFAULT), 'intentos' => 0,
            'expira_at' => date('Y-m-d H:i:s', time() + 600), 'created_at' => $now, 'updated_at' => $now,
        ]));
        $verificationId = (int) $db->insertID();
        session()->set($this->consentSessionKey($token, 'pending'), $verificationId);
        session()->remove($this->consentSessionKey($token, 'verified'));
        session()->remove($this->consentSessionKey($token, 'preview'));
        $subject = 'Codigo de verificacion para autorizacion de datos';
        $html = '<p>Tu codigo de verificacion es <strong>' . $code . '</strong>.</p><p>Vence en 10 minutos. Si no solicitaste este codigo, ignora el mensaje.</p>';
        if (! $this->sendTracked((int) $context['cliente']['id'], null, 'verificacion_consentimiento', $data['titular_email'], $subject, $html)) {
            return redirect()->back()->with('error', 'No fue posible enviar el codigo. Verifica el correo o intenta nuevamente.');
        }
        return redirect()->to(base_url('privacidad/' . $token) . '#decision')->with('success', 'Enviamos un codigo de verificacion al correo indicado.');
    }

    public function verifyConsentCode(string $token)
    {
        $context = $this->context($token);
        $verificationId = (int) session()->get($this->consentSessionKey($token, 'pending'));
        $db = db_connect();
        $row = $context && $verificationId ? $db->table('dp_consentimiento_verificaciones')->where('cliente_id', $context['cliente']['id'])->where('id', $verificationId)->get()->getRowArray() : null;
        if ($row) { $row = $this->vault()->decryptRow('dp_consentimiento_verificaciones', $row); }
        $code = trim((string) $this->request->getPost('codigo'));
        if (! $row || $row['consumido_at'] || $row['verificado_at'] || $row['expira_at'] < date('Y-m-d H:i:s') || (int) $row['intentos'] >= 5) {
            return redirect()->back()->with('error', 'El codigo vencio o ya no es valido. Solicita uno nuevo.');
        }
        $db->table('dp_consentimiento_verificaciones')->where('id', $verificationId)->update(['intentos' => (int) $row['intentos'] + 1, 'updated_at' => date('Y-m-d H:i:s')]);
        if (! password_verify($code, $row['codigo_hash'])) {
            return redirect()->back()->with('error', 'El codigo de verificacion no es correcto.');
        }
        $db->table('dp_consentimiento_verificaciones')->where('id', $verificationId)->update(['verificado_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        session()->set($this->consentSessionKey($token, 'verified'), $verificationId);
        session()->remove($this->consentSessionKey($token, 'pending'));
        session()->remove($this->consentSessionKey($token, 'preview'));
        return redirect()->to(base_url('privacidad/' . $token) . '#decision')->with('success', 'Identidad verificada. Ya puedes registrar tus decisiones.');
    }

    public function consent(string $token)
    {
        $context = $this->context($token);
        if (! $context) {
            return view('privacy/public_not_found');
        }
        if (empty($context['verifiedIdentity']) || empty($context['verification'])) {
            return redirect()->back()->with('error', 'Verifica tu identidad por correo antes de registrar decisiones.');
        }
        $data = $context['verifiedIdentity'];
        $housingUnit = ! empty($data['inmueble_id'])
            ? $this->findHousingUnit((int) $context['cliente']['id'], (int) $data['inmueble_id'])
            : null;
        if ($this->requiresHousingUnit((string) ($data['tipo_titular'] ?? '')) && ! $housingUnit) {
            session()->remove($this->consentSessionKey($token, 'verified'));
            session()->remove($this->consentSessionKey($token, 'preview'));
            return redirect()->back()->with('error', 'La unidad habitacional ya no esta disponible. Identificate nuevamente y selecciona una unidad valida.');
        }
        $data['inmueble_id'] = $housingUnit['id'] ?? null;
        $data['inmueble_label'] = $housingUnit ? $this->housingUnitLabel($housingUnit) : null;
        $authorization = $context['authorization'];
        if (! $authorization || ! hash_equals((string) $authorization['hash_sha256'], (string) $this->request->getPost('documento_hash'))) {
            return redirect()->back()->withInput()->with('error', 'La version de la autorizacion cambio. Revisa nuevamente el documento antes de decidir.');
        }
        $action = (string) ($this->request->getPost('action') ?: 'preview');
        $storedPreview = session()->get($this->consentSessionKey($token, 'preview'));
        $now = $action === 'confirm' && is_array($storedPreview) ? (string) $storedPreview['now'] : date('Y-m-d H:i:s');
        $answers = (array) $this->request->getPost('finalidad_decision');
        $accepted = [];
        $rejected = [];
        $decisionVector = [];
        foreach ($context['consentPurposes'] as $purpose) {
            $purposeId = (int) $purpose['id'];
            $answer = (string) ($answers[$purposeId] ?? '');
            if (! in_array($answer, ['autoriza', 'no_autoriza'], true)) {
                return redirect()->back()->withInput()->with('error', 'Debes responder Autorizo o No autorizo en cada finalidad.');
            }
            $decisionVector[(string) $purposeId] = ['decision' => $answer, 'tipo' => 'finalidad',
                'version' => (int) ($purpose['version'] ?? 1), 'contenido_hash' => $purpose['contenido_hash'] ?? null, 'decidido_at' => $now];
            if ($answer === 'autoriza') {
                $accepted[] = $purposeId;
            } else {
                $rejected[] = $purposeId;
            }
        }
        $sensitiveAnswers = (array) $this->request->getPost('sensible_decision');
        foreach ($context['sensitiveItems'] as $item) {
            $answer = (string) ($sensitiveAnswers[$item['id']] ?? '');
            if (! in_array($answer, ['autoriza', 'no_autoriza'], true)) {
                return redirect()->back()->withInput()->with('error', 'Debes responder de forma separada sobre cada dato sensible.');
            }
            $decisionVector['sensible:' . $item['id']] = ['decision' => $answer, 'tipo' => 'dato_sensible',
                'dato' => $item['dato'], 'finalidad_exclusiva' => $item['finalidad_exclusiva'], 'decidido_at' => $now];
        }
        $config = $context['config'];
        if (! empty($config['usa_biometria']) || ! empty($config['video_identificacion_biometrica'])) {
            $biometricDecision = (string) $this->request->getPost('biometria_decision');
            if (! in_array($biometricDecision, ['autoriza', 'no_autoriza'], true)) {
                return redirect()->back()->withInput()->with('error', 'Debes registrar una decision expresa sobre biometria.');
            }
            $decisionVector['biometria'] = ['decision' => $biometricDecision, 'tipo' => 'biometria',
                'alternativa' => $config['alternativa_biometrica'] ?? null, 'decidido_at' => $now];
        }
        if (! empty($config['transferencia_internacional']) && ! empty($config['transferencia_requiere_autorizacion'])) {
            $transferDecision = (string) $this->request->getPost('transferencia_decision');
            if (! in_array($transferDecision, ['autoriza', 'no_autoriza'], true)) {
                return redirect()->back()->withInput()->with('error', 'Debes responder sobre la transferencia internacional.');
            }
            $decisionVector['transferencia_internacional'] = ['decision' => $transferDecision, 'tipo' => 'transferencia_internacional',
                'receptor' => $config['receptor_exterior'], 'pais' => $config['paises_transferencia'],
                'garantia' => $config['garantia_transferencia'], 'garantia_detalle' => $config['garantia_transferencia_detalle'] ?? null,
                'decidido_at' => $now];
        }
        if (($data['calidad_otorgante'] ?? '') === 'representante_menor') {
            $minorDecision = (string) $this->request->getPost('menor_decision');
            if (! in_array($minorDecision, ['autoriza', 'no_autoriza'], true)) {
                return redirect()->back()->withInput()->with('error', 'El representante debe registrar su decision sobre los datos del menor.');
            }
            $decisionVector['menor'] = ['decision' => $minorDecision, 'tipo' => 'menor', 'decidido_at' => $now];
        }
        if ($decisionVector === []) {
            return redirect()->back()->with('error', 'No existen finalidades que requieran autorizacion en esta version.');
        }
        $authorizedAnswers = count(array_filter($decisionVector, static fn ($answer) => ($answer['decision'] ?? '') === 'autoriza'));
        $rejectedAnswers = count($decisionVector) - $authorizedAnswers;
        $decision = $authorizedAnswers === 0 ? 'negado' : ($rejectedAnswers === 0 ? 'autorizado' : 'parcial');
        $representativeName = $data['representante_nombre'] ?? null;
        $representativeDocument = $data['representante_documento'] ?? null;
        $representationQuality = $data['calidad_representacion'] ?? null;
        $minorOpinion = $data['opinion_menor'] ?? null;
        if ($data['calidad_otorgante'] !== 'titular' && (! $representativeName || ! $representativeDocument || ! $representationQuality)) {
            return redirect()->back()->withInput()->with('error', 'El representante o apoderado debe identificarse y acreditar la calidad en que actua.');
        }
        if ($data['calidad_otorgante'] === 'representante_menor' && ! $minorOpinion) {
            return redirect()->back()->withInput()->with('error', 'Registra la constancia de escucha y valoracion de la opinion del menor.');
        }
        $instance = $this->consentInstance($context, $data, $decisionVector, $now, $representativeName, $representationQuality);
        if (preg_match('/\{\{|\}\}|\[[A-ZÁÉÍÓÚÑ_ ]+\]/u', $instance)) {
            return redirect()->back()->with('error', 'La instancia contiene variables sin resolver y no puede registrarse.');
        }
        $instanceHash = hash('sha256', $instance);
        if ($action === 'preview') {
            $preview = ['instance' => $instance, 'instance_hash' => $instanceHash, 'document_hash' => $authorization['hash_sha256'],
                'now' => $now, 'decision_vector' => $decisionVector];
            session()->set($this->consentSessionKey($token, 'preview'), $preview);
            return view('privacy/public_portal', array_merge($context, ['success' => false, 'consentPreview' => $preview]));
        }
        if ($action !== 'confirm' || ! is_array($storedPreview)
            || ! hash_equals((string) $storedPreview['document_hash'], (string) $authorization['hash_sha256'])
            || ! hash_equals((string) $storedPreview['instance_hash'], $instanceHash)) {
            session()->remove($this->consentSessionKey($token, 'preview'));
            return redirect()->back()->with('error', 'La vista previa cambio. Revisa nuevamente la instancia antes de firmar.');
        }
        $signature = $this->signature((string) $this->request->getPost('firma_imagen'));
        if ($signature === null) {
            return redirect()->back()->withInput()->with('error', 'La decision requiere una firma o manifestacion electronica verificable.');
        }
        $evidence = [
            'cliente_id' => (int) $context['cliente']['id'], 'documento_id' => $authorization['id'] ?? null,
            'inmueble_id' => $data['inmueble_id'], 'unidad_habitacional' => $data['inmueble_label'],
            'documento_hash' => $authorization['hash_sha256'] ?? null, 'decision' => $decision,
            'vector' => $decisionVector, 'instancia_hash' => $instanceHash, 'titular_documento' => $data['titular_documento'],
            'fecha' => $now, 'zona_horaria' => 'America/Bogota', 'canal' => 'portal_web',
            'firma_hash' => hash('sha256', $signature),
            'soporte_representacion_hash' => $context['verification']['soporte_representacion_hash'] ?? null,
            'ip' => $this->request->getIPAddress(), 'user_agent' => substr((string) $this->request->getUserAgent(), 0, 500),
        ];
        $evidenceHash = hash('sha256', json_encode($evidence, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $id = (new DpConsentimientoModel())->insert([
            'cliente_id' => $context['cliente']['id'], 'inmueble_id' => $data['inmueble_id'],
            'documento_id' => $authorization['id'] ?? null,
            'documento_version' => $authorization['version'], 'documento_hash' => $authorization['hash_sha256'],
            'instancia_html' => $instance, 'instancia_hash' => $instanceHash,
            'tipo_titular' => $data['tipo_titular'], 'titular_nombre' => $data['titular_nombre'],
            'titular_tipo_documento' => $data['titular_tipo_documento'] ?: null, 'titular_documento' => $data['titular_documento'],
            'titular_email' => $data['titular_email'], 'representante_nombre' => $representativeName,
            'calidad_otorgante' => $data['calidad_otorgante'], 'representante_documento' => $representativeDocument,
            'calidad_representacion' => $representationQuality,
            'soporte_representacion_ruta' => $context['verification']['soporte_representacion_ruta'],
            'soporte_representacion_hash' => $context['verification']['soporte_representacion_hash'], 'opinion_menor' => $minorOpinion,
            'decision' => $decision, 'decision_vector_json' => json_encode($decisionVector), 'finalidades_aceptadas_json' => json_encode($accepted),
            'finalidades_rechazadas_json' => json_encode($rejected), 'firma_imagen' => $signature,
            'evidencia_hash' => $evidenceHash, 'canal' => 'portal_web', 'tipo_evidencia' => 'firma_electronica',
            'verificacion_identidad' => 'codigo_de_un_solo_uso_enviado_al_correo_y_firma_electronica',
            'ip' => $this->request->getIPAddress(), 'user_agent' => substr((string) $this->request->getUserAgent(), 0, 500),
            'zona_horaria' => 'America/Bogota', 'otorgado_at' => $now,
        ], true);
        db_connect()->table('dp_consentimiento_eventos')->insert($this->vault()->encryptRow('dp_consentimiento_eventos', [
            'cliente_id' => $context['cliente']['id'], 'consentimiento_id' => $id, 'tipo' => 'otorgamiento',
            'alcance_json' => json_encode($decisionVector), 'instancia_hash' => $instanceHash, 'evidencia_hash' => $evidenceHash,
            'canal' => 'portal_web', 'ip' => $this->request->getIPAddress(),
            'user_agent' => substr((string) $this->request->getUserAgent(), 0, 500), 'ocurrido_at' => $now, 'created_at' => $now,
        ]));
        db_connect()->table('dp_consentimiento_verificaciones')->where('id', $context['verification']['id'])->update(['consumido_at' => $now, 'updated_at' => $now]);
        session()->remove($this->consentSessionKey($token, 'verified'));
        session()->remove($this->consentSessionKey($token, 'preview'));
        if ($decision !== 'autorizado') {
            $this->upsertExclusion((int) $context['cliente']['id'], $data['titular_documento'], $data['titular_email'], $decision, $rejected, 'consentimiento');
        }
        PrivacyAudit::record((int) $context['cliente']['id'], 'registrar_decision', 'consentimiento', (int) $id, null,
            ['decision' => $decision, 'inmueble_id' => $data['inmueble_id'], 'evidencia_hash' => hash('sha256', json_encode($evidence))], 'titular');
        $consent = (new DpConsentimientoModel())->find($id);
        $pdfPath = (new PrivacyPdf())->consent($consent, $context['cliente'], $context['consentPurposes'], $authorization);
        $this->notifyConsent($context['cliente'], $data, $decision, (int) $id, WRITEPATH . $pdfPath);
        return view('privacy/public_portal', $context + ['success' => true, 'confirmation' => 'Tu decision fue registrada con la constancia DP-C-' . $id . '.']);
    }

    public function request(string $token)
    {
        $context = $this->context($token);
        if (! $context) {
            return view('privacy/public_not_found');
        }
        $type = (string) $this->request->getPost('tipo');
        $received = date('Y-m-d H:i:s');
        $legalReceipt = PrivacyBusinessDays::legalReceipt($received, (string) ($context['config']['request_cutoff_time'] ?? '17:00'));
        $text = trim((string) $this->request->getPost('solicitud_texto'));
        if ($type === 'consulta' && $text === '') {
            $text = 'Consulta general sobre los datos personales del titular.';
        }
        $data = [
            'cliente_id' => $context['cliente']['id'], 'radicado' => $this->radicado((int) $context['cliente']['id']),
            'tipo' => $type, 'clasificacion_original' => $type, 'titular_nombre' => trim((string) $this->request->getPost('titular_nombre')),
            'titular_documento' => $this->nullable('titular_documento'),
            'titular_email' => trim((string) $this->request->getPost('titular_email')),
            'canal' => 'portal_web', 'calidad_solicitante' => (string) ($this->request->getPost('calidad_solicitante') ?: 'titular'),
            'legitimacion_tipo' => $this->nullable('legitimacion_tipo'), 'solicitud_texto' => $text,
            'estado' => $type === 'consulta' ? 'recibida' : 'pendiente_identidad', 'identidad_estado' => 'pendiente', 'recibida_at' => $received,
            'fecha_ingreso_real' => $received, 'fecha_recepcion_legal' => $legalReceipt, 'acuse_at' => $received,
            'completa_at' => $type === 'consulta' ? $received : null,
            'vence_at' => $type === 'consulta' ? PrivacyRequestWorkflow::initialDeadline($type, $legalReceipt) : null,
        ];
        $data['acuse_hash'] = hash('sha256', json_encode([$data['radicado'], $received, $legalReceipt, $type], JSON_UNESCAPED_UNICODE));
        if (! $this->validateData($data, [
            'tipo' => 'required|in_list[consulta,reclamo,rectificacion,actualizacion,revocatoria,supresion]',
            'titular_nombre' => 'required|max_length[191]', 'titular_email' => 'required|valid_email|max_length[191]',
            'solicitud_texto' => $type === 'consulta' ? 'required' : 'required|min_length[10]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $db = db_connect();
        $db->transStart();
        $id = (int) (new DpSolicitudModel())->insert($data, true);
        foreach ($context['bases'] as $base) {
            $db->table('dp_solicitud_bases')->insert(['solicitud_id' => $id, 'base_id' => $base['id'],
                'accion' => 'por_evaluar', 'estado' => 'pendiente', 'created_at' => $received, 'updated_at' => $received]);
        }
        foreach ($db->table('dp_terceros')->where('cliente_id', $context['cliente']['id'])->where('activo', 1)->get()->getResultArray() as $thirdParty) {
            $db->table('dp_solicitud_terceros')->insert(['solicitud_id' => $id, 'tercero_id' => $thirdParty['id'],
                'accion' => 'por_evaluar', 'estado' => 'pendiente', 'created_at' => $received, 'updated_at' => $received]);
        }
        $this->requestEvent($id, 'radicacion', ['radicado' => $data['radicado'], 'recepcion_real' => $received, 'recepcion_legal' => $legalReceipt, 'acuse_hash' => $data['acuse_hash']]);
        $db->transComplete();
        PrivacyAudit::record((int) $context['cliente']['id'], 'radicar', 'solicitud', $id, null, ['radicado' => $data['radicado'], 'tipo' => $type], 'titular');
        $this->notifyReceipt($context['cliente'], $data, $id);
        return view('privacy/public_portal', $context + ['success' => true, 'confirmation' => 'Tu solicitud fue radicada como ' . $data['radicado'] . '. Conserva este numero.']);
    }

    public function publicDocument(string $token, int $id)
    {
        $context = $this->context($token);
        $document = $context ? (new DpDocumentoModel())->where('cliente_id', $context['cliente']['id'])
            ->where('id', $id)->where('estado', 'publicado')->first() : null;
        if (! $document || ! hash_equals((string) $document['hash_sha256'], hash('sha256', (string) $document['contenido_html']))) {
            return view('privacy/public_not_found');
        }
        $path = $document['pdf_ruta'] && is_file(WRITEPATH . $document['pdf_ruta'])
            ? $document['pdf_ruta'] : (new PrivacyPdf())->document($document, $context['cliente']);
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . url_title($document['titulo'], '-', true) . '.pdf"')
            ->setBody((new PrivacyPdf())->contents($path));
    }

    public function sendgridWebhook(string $secret)
    {
        $configured = trim((string) env('sendgrid.webhookToken'));
        if ($configured === '' || ! hash_equals($configured, $secret)) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }
        $events = $this->request->getJSON(true);
        if (! is_array($events)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        }
        $db = db_connect();
        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }
            $messageId = preg_replace('/\.filter\d+\.\d+$/', '', (string) ($event['sg_message_id'] ?? ''));
            if ($messageId === '') {
                continue;
            }
            $notification = $db->table('dp_notificaciones')->where('proveedor_id', $messageId)->get()->getRowArray();
            if (! $notification) {
                continue;
            }
            $kind = (string) ($event['event'] ?? 'unknown');
            $db->table('dp_notificacion_eventos')->insert($this->vault()->encryptRow('dp_notificacion_eventos', [
                'notificacion_id' => $notification['id'], 'evento' => $kind,
                'proveedor_evento_id' => $event['sg_event_id'] ?? null,
                'payload_json' => json_encode($this->redactEvent($event), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ocurrido_at' => isset($event['timestamp']) ? date('Y-m-d H:i:s', (int) $event['timestamp']) : date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ]));
            $state = match ($kind) {
                'delivered' => 'entregado', 'bounce' => 'rebotado', 'dropped' => 'bloqueado',
                'spamreport' => 'spam', 'deferred' => 'diferido', default => $notification['estado'],
            };
            $update = ['estado' => $state, 'updated_at' => date('Y-m-d H:i:s')];
            if ($kind === 'delivered') {
                $update['entregado_at'] = date('Y-m-d H:i:s');
            }
            $db->table('dp_notificaciones')->where('id', $notification['id'])->update($update);
        }
        return $this->response->setJSON(['ok' => true]);
    }

    private function context(string $token): ?array
    {
        $program = (new DpProgramaModel())->where('public_token', $token)->whereIn('estado', ['configuracion', 'activo', 'en_revision'])->first();
        if (! $program) {
            return null;
        }
        $cliente = (new ClienteModel())->find((int) $program['cliente_id']);
        if (! $cliente || (int) $cliente['activo'] !== 1) {
            return null;
        }
        $db = db_connect();
        $documents = $db->table('dp_documentos')->where('cliente_id', $cliente['id'])->where('estado', 'publicado')
            ->orderBy('version', 'DESC')->get()->getResultArray();
        $latest = [];
        foreach ($documents as $document) {
            $latest[$document['tipo']] ??= $document;
        }
        if (isset($latest['autorizacion'])) {
            $authorizationVariables = json_decode((string) ($latest['autorizacion']['variables_json'] ?? '{}'), true) ?: [];
            if ((int) ($authorizationVariables['consent_schema_version'] ?? 0) < 2) {
                unset($latest['autorizacion']);
            }
        }
        if (isset($latest['autorizacion']) && ! isset($latest['politica'])) {
            unset($latest['autorizacion']);
        }
        $purposes = $db->table('dp_finalidades f')->select('f.*, b.nombre AS base_nombre, b.datos_sensibles, b.datos_menores')
            ->join('dp_bases_datos b', 'b.id=f.base_id')->where('f.cliente_id', $cliente['id'])->where('f.activo', 1)->orderBy('b.nombre')->get()->getResultArray();
        $sensitiveItems = $db->table('dp_finalidad_datos_sensibles')->where('cliente_id', $cliente['id'])->where('activo', 1)->orderBy('dato')->get()->getResultArray();
        foreach ($purposes as &$purpose) {
            $purpose['datos_sensibles_detalle'] = array_values(array_filter($sensitiveItems, static fn ($item) => (int) $item['finalidad_id'] === (int) $purpose['id']));
        }
        unset($purpose);
        if (isset($latest['autorizacion'])) {
            $authorizationVariables = json_decode((string) $latest['autorizacion']['variables_json'], true) ?: [];
            $boundPurposes = $authorizationVariables['purpose_versions'] ?? [];
            if (count($boundPurposes) !== count($purposes)) {
                unset($latest['autorizacion']);
            }
            foreach ($purposes as $purpose) {
                if (! isset($latest['autorizacion'])) {
                    break;
                }
                $bound = $boundPurposes[(string) $purpose['id']] ?? null;
                if (! $bound || (int) $bound['version'] !== (int) ($purpose['version'] ?? 1) || ! hash_equals((string) ($bound['hash'] ?? ''), (string) ($purpose['contenido_hash'] ?? ''))) {
                    unset($latest['autorizacion']);
                    break;
                }
            }
        }
        $consentPurposeIds = array_map('intval', array_column(array_filter($purposes, static fn ($purpose) => ($purpose['base_juridica_tipo'] ?? 'autorizacion') === 'autorizacion'), 'id'));
        $sensitiveDecisionItems = array_values(array_filter($sensitiveItems, static fn ($item) => in_array((int) $item['finalidad_id'], $consentPurposeIds, true)));
        $config = json_decode((string) ($program['config_json'] ?? '{}'), true) ?: [];
        $verification = null;
        $verifiedIdentity = null;
        $verifiedId = (int) session()->get($this->consentSessionKey($token, 'verified'));
        if ($verifiedId && isset($latest['autorizacion'])) {
            $verification = $db->table('dp_consentimiento_verificaciones')->where('id', $verifiedId)
                ->where('cliente_id', $cliente['id'])->where('documento_hash', $latest['autorizacion']['hash_sha256'])
                ->where('verificado_at IS NOT NULL', null, false)->where('consumido_at', null)
                ->where('expira_at >=', date('Y-m-d H:i:s'))->get()->getRowArray();
            if ($verification) { $verification = $this->vault()->decryptRow('dp_consentimiento_verificaciones', $verification); }
            $verifiedIdentity = $verification ? (json_decode($verification['datos_json'], true) ?: null) : null;
        }
        $pendingVerification = null;
        $pendingId = (int) session()->get($this->consentSessionKey($token, 'pending'));
        if ($pendingId) {
            $pendingVerification = $db->table('dp_consentimiento_verificaciones')->select('id, email, expira_at, intentos')
                ->where('id', $pendingId)->where('cliente_id', $cliente['id'])->where('consumido_at', null)->get()->getRowArray();
            if ($pendingVerification) { $pendingVerification = $this->vault()->decryptRow('dp_consentimiento_verificaciones', $pendingVerification); }
        }
        $consentPreview = session()->get($this->consentSessionKey($token, 'preview'));
        return [
            'cliente' => $cliente, 'programa' => $program, 'token' => $token,
            'housingUnits' => $this->housingUnits((int) $cliente['id']),
            'bases' => $this->vault()->decryptRows('dp_bases_datos', $db->table('dp_bases_datos')->where('cliente_id', $cliente['id'])->where('activo', 1)->orderBy('nombre')->get()->getResultArray()),
            'finalidades' => $purposes,
            'consentPurposes' => array_values(array_filter($purposes, static fn ($purpose) => ($purpose['base_juridica_tipo'] ?? 'autorizacion') === 'autorizacion')),
            'informedPurposes' => array_values(array_filter($purposes, static fn ($purpose) => ($purpose['base_juridica_tipo'] ?? 'autorizacion') === 'excepcion_legal')),
            'sensitiveItems' => $sensitiveDecisionItems,
            'config' => $config, 'documents' => $latest, 'authorization' => $latest['autorizacion'] ?? null,
            'verification' => $verification, 'verifiedIdentity' => $verifiedIdentity, 'pendingVerification' => $pendingVerification,
            'consentPreview' => is_array($consentPreview) ? $consentPreview : null,
        ];
    }

    private function housingUnits(int $clienteId): array
    {
        return db_connect()->table('inmuebles i')
            ->select('i.id, i.tipo, i.identificador, i.piso, i.torre_id, t.nombre AS torre_nombre')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('i.cliente_id', $clienteId)
            ->where('i.deleted_at', null)
            ->orderBy('i.tipo', 'ASC')
            ->orderBy('t.nombre', 'ASC')
            ->orderBy('i.identificador', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function findHousingUnit(int $clienteId, int $inmuebleId): ?array
    {
        if ($inmuebleId < 1) {
            return null;
        }

        return db_connect()->table('inmuebles i')
            ->select('i.id, i.tipo, i.identificador, i.piso, i.torre_id, t.nombre AS torre_nombre')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('i.cliente_id', $clienteId)
            ->where('i.id', $inmuebleId)
            ->where('i.deleted_at', null)
            ->get()
            ->getRowArray() ?: null;
    }

    private function requiresHousingUnit(string $holderType): bool
    {
        return in_array(mb_strtolower(trim($holderType)), self::HOUSING_HOLDER_TYPES, true);
    }

    private function housingUnitLabel(array $unit): string
    {
        $tower = trim((string) ($unit['torre_nombre'] ?? ''));
        $identifier = trim((string) ($unit['identificador'] ?? ''));

        return $tower !== '' ? $tower . ' - ' . $identifier : $identifier;
    }

    private function notifyConsent(array $cliente, array $data, string $decision, int $consentId, string $pdfPath): void
    {
        $subject = 'Constancia de decision sobre datos personales - DP-C-' . $consentId;
        $html = '<p>Hola ' . esc($data['titular_nombre']) . ',</p><p>Registramos tu decision: <strong>' . esc($decision) .
            '</strong>. Constancia DP-C-' . $consentId . '.</p><p>Puedes ejercer tus derechos mediante los canales informados por ' . esc($cliente['nombre_tercero']) . '.</p>';
        $this->sendTracked((int) $cliente['id'], null, 'constancia_consentimiento', $data['titular_email'], $subject, $html, $pdfPath);
    }

    private function consentInstance(array $context, array $data, array $decisionVector, string $now, ?string $representativeName, ?string $representationQuality): string
    {
        $rows = '';
        $authorized = [];
        $rejected = [];
        foreach ($context['consentPurposes'] as $purpose) {
            $answerRecord = $decisionVector[(string) $purpose['id']] ?? [];
            $answer = $answerRecord['decision'] ?? 'no_autoriza';
            $categories = json_decode((string) ($purpose['categorias_datos_json'] ?? '[]'), true) ?: [];
            $label = (string) $purpose['descripcion'];
            if ($answer === 'autoriza') {
                $authorized[] = $label;
            } else {
                $rejected[] = $label;
            }
            $rows .= '<tr><td>' . esc($purpose['base_nombre']) . '</td><td>' . esc($label) . '</td><td>' . esc(implode(', ', $categories)) . '</td><td>' . ($answer === 'autoriza' ? 'Autorizo' : 'No autorizo') . '</td></tr>';
        }
        $specialRows = '';
        foreach ($decisionVector as $key => $record) {
            if (($record['tipo'] ?? '') === 'dato_sensible') {
                $specialRows .= '<tr><td>Dato sensible</td><td>' . esc($record['dato']) . ': ' . esc($record['finalidad_exclusiva']) . '</td><td>' . (($record['decision'] ?? '') === 'autoriza' ? 'Autorizo expresamente' : 'No autorizo') . '</td></tr>';
            }
        }
        $sensitive = $specialRows !== '' ? '<h2>Decisiones sobre datos sensibles</h2><table><thead><tr><th>Tipo</th><th>Dato y finalidad</th><th>Decision</th></tr></thead><tbody>' . $specialRows . '</tbody></table>' : '';
        $biometric = isset($decisionVector['biometria'])
            ? '<p><strong>Decision biometrica:</strong> ' . (($decisionVector['biometria']['decision'] ?? '') === 'autoriza' ? 'Autorizo expresamente' : 'No autorizo; utilizare la alternativa no biometrica') . '.</p>'
            : '';
        $transfer = isset($decisionVector['transferencia_internacional'])
            ? '<p><strong>Transferencia internacional:</strong> ' . (($decisionVector['transferencia_internacional']['decision'] ?? '') === 'autoriza' ? 'Autorizo expresamente' : 'No autorizo') . '. Receptor: ' . esc($decisionVector['transferencia_internacional']['receptor']) . '; pais: ' . esc($decisionVector['transferencia_internacional']['pais']) . '; garantia: ' . esc($decisionVector['transferencia_internacional']['garantia']) . ' ' . esc($decisionVector['transferencia_internacional']['garantia_detalle'] ?? '') . '.</p>' : '';
        $minor = isset($decisionVector['menor']) ? '<p><strong>Decision del representante sobre datos del menor:</strong> ' . (($decisionVector['menor']['decision'] ?? '') === 'autoriza' ? 'Autorizo' : 'No autorizo') . '.</p>' : '';
        $representation = $data['calidad_otorgante'] === 'titular' ? ''
            : '<p><strong>Otorgante:</strong> ' . esc($representativeName) . '. <strong>Calidad:</strong> ' . esc($representationQuality) . '. <strong>Hash del soporte:</strong> ' . esc($context['verification']['soporte_representacion_hash'] ?? '') . '.</p>';
        return '<article class="legal-document"><header><h1>Instancia de Autorizacion para el Tratamiento de Datos Personales</h1><p>Version ' . esc($context['authorization']['version']) . '</p></header>' .
            $context['authorization']['contenido_html'] .
            (isset($context['documents']['politica']) ? '<p><strong>Politica vigente vinculada:</strong> version ' . esc($context['documents']['politica']['version']) . ', hash SHA-256 ' . esc($context['documents']['politica']['hash_sha256']) . '.</p>' : '') .
            '<h2>Identidad y legitimacion</h2><p><strong>Titular:</strong> ' . esc($data['titular_nombre']) . '<br><strong>Documento:</strong> ' . esc(trim($data['titular_tipo_documento'] . ' ' . $data['titular_documento'])) . '<br><strong>Perfil:</strong> ' . esc($data['tipo_titular']) . ($data['inmueble_label'] ? '<br><strong>Unidad habitacional declarada:</strong> ' . esc($data['inmueble_label']) : '') . '<br><strong>Calidad del otorgante:</strong> ' . esc($data['calidad_otorgante']) . '</p>' . $representation .
            '<h2>Vector de decisiones</h2><table><thead><tr><th>Base</th><th>Finalidad</th><th>Datos</th><th>Decision</th></tr></thead><tbody>' . $rows . '</tbody></table>' . $sensitive . $biometric . $transfer . $minor .
            '<p><strong>Finalidades autorizadas:</strong> ' . esc(implode('; ', $authorized) ?: 'Ninguna') . '.<br><strong>Finalidades no autorizadas:</strong> ' . esc(implode('; ', $rejected) ?: 'Ninguna') . '.</p>' .
            '<h2>Evidencia informada</h2><p>Fecha y hora del servidor: ' . esc($now) . ' (America/Bogota). Canal: portal web. Tipo de aceptacion: firma electronica. Metodo de verificacion: codigo de un solo uso enviado al correo y firma electronica. Se conservan IP e identificador del navegador exclusivamente para probar esta decision; no se captura geolocalizacion ni huella ampliada del dispositivo.</p></article>';
    }

    private function notifyReceipt(array $cliente, array $data, int $requestId): void
    {
        $subject = 'Solicitud de datos personales recibida - ' . $data['radicado'];
        $html = '<p>Hola ' . esc($data['titular_nombre']) . ',</p><p>Tu solicitud fue recibida con radicado <strong>' . esc($data['radicado']) .
            '</strong>.</p><p>Ingreso real: ' . esc($data['fecha_ingreso_real']) . '. Fecha de recepcion para computo: ' . esc($data['fecha_recepcion_legal']) .
            '.</p><p>Clasificacion inicial: ' . esc($data['tipo']) . '. Validaremos identidad y legitimacion de forma proporcional. ' .
            ($data['tipo'] === 'consulta' ? 'La fecha limite inicial es ' . esc($data['vence_at']) . '.' : 'El termino de fondo iniciara cuando el reclamo quede completo.') . '</p>';
        $this->sendTracked((int) $cliente['id'], $requestId, 'acuse_solicitud', $data['titular_email'], $subject, $html);
    }

    private function requestEvent(int $requestId, string $type, array $detail): void
    {
        $occurredAt = date('Y-m-d H:i:s');
        $payload = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        db_connect()->table('dp_solicitud_eventos')->insert($this->vault()->encryptRow('dp_solicitud_eventos', [
            'solicitud_id' => $requestId, 'tipo' => $type, 'detalle_json' => $payload,
            'evento_hash' => hash('sha256', $requestId . '|' . $type . '|' . $occurredAt . '|' . $payload),
            'usuario_id' => null, 'ocurrido_at' => $occurredAt, 'created_at' => $occurredAt,
        ]));
    }

    private function sendTracked(int $clienteId, ?int $requestId, string $type, string $to, string $subject, string $html, ?string $pdfPath = null): bool
    {
        $model = new DpNotificacionModel();
        $id = (int) $model->insert(['cliente_id' => $clienteId, 'solicitud_id' => $requestId, 'tipo' => $type,
            'destinatario' => $to, 'asunto' => $subject, 'plantilla' => $type, 'contenido_hash' => hash('sha256', $html),
            'estado' => 'pendiente', 'intentos' => 1], true);
        $result = (new EmailService())->sendPrivacyMessage($to, $subject, $html, $pdfPath, $clienteId);
        $model->update($id, ['proveedor_id' => $result['message_id'], 'estado' => $result['success'] ? 'aceptado' : 'fallido',
            'ultimo_error' => $result['error'], 'enviado_at' => $result['success'] ? date('Y-m-d H:i:s') : null]);
        return $result['success'];
    }

    private function upsertExclusion(int $clienteId, string $identifier, string $email, string $scope, array $purposes, string $origin): void
    {
        $db = db_connect();
        $hash = $this->identifierHash($identifier);
        $row = $db->table('dp_exclusiones')->where('cliente_id', $clienteId)->where('identificador_hash', $hash)->get()->getRowArray();
        $data = ['cliente_id' => $clienteId, 'identificador_hash' => $hash, 'email_hash' => hash('sha256', mb_strtolower($email)),
            'alcance' => $scope === 'negado' ? 'total' : 'parcial', 'finalidades_json' => json_encode($purposes),
            'origen' => $origin, 'activo' => 1, 'updated_at' => date('Y-m-d H:i:s')];
        if ($row) {
            $db->table('dp_exclusiones')->where('id', $row['id'])->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->table('dp_exclusiones')->insert($data);
        }
    }

    private function signature(string $data): ?string
    {
        if ($data === '') {
            return null;
        }
        if (! preg_match('#^data:image/(png|jpeg);base64,([A-Za-z0-9+/=]+)$#', $data, $match)) {
            return null;
        }
        $binary = base64_decode($match[2], true);
        return $binary !== false && strlen($binary) <= 1024 * 1024 ? $data : null;
    }

    /** @return array{path: string, hash: string}|null */
    private function saveRepresentationSupport(int $clienteId): ?array
    {
        $file = $this->request->getFile('soporte_representacion');
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (! $file->isValid() || $file->getSize() > 5 * 1024 * 1024 || ! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'application/pdf'], true)) {
            throw new \RuntimeException('El soporte debe ser JPG, PNG o PDF y pesar maximo 5 MB.');
        }
        $dir = 'uploads/privacy/representation/cliente-' . $clienteId . '/' . date('Ymd');
        $absolute = WRITEPATH . $dir;
        if (! is_dir($absolute) && ! mkdir($absolute, 0775, true) && ! is_dir($absolute)) {
            throw new \RuntimeException('No fue posible almacenar el soporte de representacion.');
        }
        $plain = file_get_contents($file->getTempName());
        if ($plain === false) {
            throw new \RuntimeException('No fue posible leer el soporte de representacion.');
        }
        $name = bin2hex(random_bytes(16)) . '.enc';
        $path = $dir . '/' . $name;
        $encrypted = $this->vault()->encryptFile($plain, 'representation|' . $path);
        if (file_put_contents($absolute . '/' . $name, $encrypted, LOCK_EX) === false) {
            throw new \RuntimeException('No fue posible cifrar el soporte de representacion.');
        }
        return ['path' => $path, 'hash' => hash('sha256', $plain)];
    }

    private function redactEvent(array $event): array
    {
        return array_intersect_key($event, array_flip(['event', 'email', 'timestamp', 'sg_event_id', 'sg_message_id', 'reason', 'status', 'response', 'attempt']));
    }

    private function nullable(string $key): ?string
    {
        $value = trim((string) $this->request->getPost($key));
        return $value === '' ? null : $value;
    }

    private function radicado(int $clienteId): string
    {
        return 'DP-' . date('Ymd') . '-' . $clienteId . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    private function consentSessionKey(string $token, string $state): string
    {
        return 'privacy_consent_' . $state . '_' . hash('sha256', $token);
    }

    private function identifierHash(string $identifier): string
    {
        return (string) (new PrivacyPii())->blindIndex($identifier, 'documento');
    }

    private function vault(): PrivacyVault
    {
        return new PrivacyVault();
    }
}
