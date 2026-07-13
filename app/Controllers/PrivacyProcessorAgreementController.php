<?php

namespace App\Controllers;

use App\Libraries\EmailService;
use App\Libraries\PrivacyPdf;
use App\Libraries\PrivacyBusinessDays;
use App\Libraries\PrivacyProcessorAgreementService;
use App\Libraries\PrivacyVault;

final class PrivacyProcessorAgreementController extends BaseController
{
    public function show(string $token)
    {
        $context = $this->context($token);
        if (! $context) { return view('privacy/public_not_found'); }
        if ($context['agreement']['estado'] === 'vigente') {
            return view('privacy/processor_agreement_portal', $context + ['success' => true, 'confirmation' => 'Este acuerdo ya fue suscrito y se encuentra vigente.']);
        }
        return view('privacy/processor_agreement_portal', $context + ['success' => false]);
    }

    public function confirmView(string $token)
    {
        $context = $this->context($token);
        if (! $context || $context['agreement']['estado'] !== 'pendiente_firma') { return view('privacy/public_not_found'); }
        if (empty($context['agreement']['vista_at'])) {
            $now = date('Y-m-d H:i:s'); db_connect()->table('dp_acuerdos_encargado')->where('id', $context['agreement']['id'])->update(['vista_at' => $now, 'updated_at' => $now]);
            $this->event((int) $context['agreement']['id'], 'visualizacion_completa', ['ip' => $this->request->getIPAddress()]);
        }
        return redirect()->back()->with('success', 'Lectura completa registrada. Solicite el codigo para firmar.');
    }

    public function sendCode(string $token)
    {
        $context = $this->context($token);
        if (! $context || $context['agreement']['estado'] !== 'pendiente_firma' || empty($context['agreement']['vista_at'])) { return view('privacy/public_not_found'); }
        if (service('throttler')->check('proc-code-' . $context['agreement']['id'] . '-' . $this->request->getIPAddress(), 5, 10 * MINUTE) === false) {
            return redirect()->back()->with('error', 'Se enviaron demasiados codigos. Espera unos minutos antes de solicitar otro.');
        }
        $code = (string) random_int(100000, 999999); $now = date('Y-m-d H:i:s');
        db_connect()->table('dp_acuerdos_encargado')->where('id', $context['agreement']['id'])->update(['codigo_hash' => hash_hmac('sha256', $code, $this->key()),
            'codigo_expira_at' => date('Y-m-d H:i:s', strtotime('+10 minutes')), 'codigo_intentos' => 0, 'updated_at' => $now]);
        $mail = (new EmailService())->sendPrivacyMessage($context['third']['representante_email'], 'Codigo de firma del acuerdo de transmision', '<p>Codigo de firma:</p><p style="font-size:24px;font-weight:bold;letter-spacing:4px">' . $code . '</p><p>Expira en 10 minutos.</p>', null, (int) $context['client']['id']);
        $this->event((int) $context['agreement']['id'], 'codigo_solicitado', ['envio_aceptado' => $mail['success']]);
        return redirect()->back()->with($mail['success'] ? 'success' : 'error', $mail['success'] ? 'Codigo enviado.' : 'No fue posible enviar el codigo.');
    }

    public function accept(string $token)
    {
        $context = $this->context($token); $db = db_connect();
        if (! $context || $context['agreement']['estado'] !== 'pendiente_firma') { return view('privacy/public_not_found'); }
        $agreement = $context['agreement']; $code = preg_replace('/\D/', '', (string) $this->request->getPost('codigo'));
        if (empty($agreement['vista_at']) || empty($agreement['codigo_hash']) || $agreement['codigo_expira_at'] < date('Y-m-d H:i:s') || (int) $agreement['codigo_intentos'] >= 5) {
            return redirect()->back()->with('error', 'La lectura y un codigo vigente son obligatorios.');
        }
        if (strlen($code) !== 6 || ! hash_equals($agreement['codigo_hash'], hash_hmac('sha256', $code, $this->key()))) {
            $db->table('dp_acuerdos_encargado')->where('id', $agreement['id'])->update(['codigo_intentos' => (int) $agreement['codigo_intentos'] + 1]);
            return redirect()->back()->with('error', 'Codigo invalido.');
        }
        $service = new PrivacyProcessorAgreementService();
        $master = $db->table('dp_documentos')->where('cliente_id', $agreement['cliente_id'])->where('tipo', 'encargados')->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
        if (! $service->verify($agreement['instancia_html'], $agreement['instancia_hash']) || ! $master || (int) $master['id'] !== (int) $agreement['documento_id']
            || ! hash_equals($master['hash_sha256'], hash('sha256', $master['contenido_html'])) || ! hash_equals($master['hash_sha256'], $agreement['documento_hash'])) {
            $this->event((int) $agreement['id'], 'falla_integridad', ['instancia_hash' => $agreement['instancia_hash']]);
            return redirect()->back()->with('error', 'COPIA NO VALIDA: solicite una nueva instancia.');
        }
        $signature = $this->signature((string) $this->request->getPost('firma_imagen'));
        if (! $signature || ! $this->request->getPost('acepto')) { return redirect()->back()->with('error', 'La firma y aceptacion expresa son obligatorias.'); }
        $now = date('Y-m-d H:i:s');
        $db->table('dp_acuerdos_encargado')->where('id', $agreement['id'])->update((new PrivacyVault())->encryptRow('dp_acuerdos_encargado', ['encargado_firma' => $signature, 'encargado_firma_hash' => hash('sha256', $signature),
            'encargado_firmado_at' => $now, 'encargado_ip' => $this->request->getIPAddress(), 'encargado_user_agent' => substr((string) $this->request->getUserAgent(), 0, 500),
            'estado' => 'vigente', 'updated_at' => $now]));
        $db->table('dp_terceros')->where('id', $agreement['tercero_id'])->where('cliente_id', $agreement['cliente_id'])->update(['habilitado_datos' => 1,
            'contrato_vigente' => 1, 'contrato_fecha' => $agreement['vigencia_desde'], 'updated_at' => $now]);
        $this->event((int) $agreement['id'], 'doble_firma_y_habilitacion', ['hash' => $agreement['instancia_hash'], 'firma_encargado_hash' => hash('sha256', $signature)]);
        $agreement = array_merge($agreement, ['encargado_firma' => $signature, 'encargado_firmado_at' => $now]);
        $path = (new PrivacyPdf())->processorAgreement($agreement, $context['client']);
        $mail = (new EmailService())->sendPrivacyMessage($context['third']['representante_email'], 'Copia del acuerdo de transmision suscrito', '<p>Adjuntamos la instancia contractual firmada y sellada.</p>', WRITEPATH . $path, (int) $context['client']['id']);
        if ($mail['success']) { $db->table('dp_acuerdos_encargado')->where('id', $agreement['id'])->update(['copia_enviada_at' => $now]); }
        return view('privacy/processor_agreement_portal', $context + ['success' => true, 'confirmation' => 'Acuerdo suscrito. El Encargado quedo habilitado dentro del alcance firmado.']);
    }

    public function reportIncident(string $token)
    {
        $context = $this->context($token); $detail = trim((string) $this->request->getPost('detalle'));
        $detected = (string) $this->request->getPost('detectado_at'); $known = (string) $this->request->getPost('conocimiento_at');
        if (! $context || $context['agreement']['estado'] !== 'vigente' || $detail === '' || strtotime($detected) === false || strtotime($known) === false) {
            return redirect()->back()->with('error', 'El incidente exige acuerdo vigente, detalle y fechas de deteccion y conocimiento.');
        }
        $db = db_connect(); $now = date('Y-m-d H:i:s'); $knownAt = date('Y-m-d H:i:s', strtotime($known));
        $folio = 'DP-INC-E-' . $context['agreement']['cliente_id'] . '-' . date('YmdHis');
        $db->table('dp_incidentes_privacidad')->insert((new PrivacyVault())->encryptRow('dp_incidentes_privacidad', ['cliente_id' => $context['agreement']['cliente_id'], 'tercero_id' => $context['agreement']['tercero_id'],
            'acuerdo_encargado_id' => $context['agreement']['id'], 'folio' => $folio, 'tipo' => 'notificacion_encargado', 'severidad' => 'S2',
            'fuente' => 'Encargado: ' . $context['third']['nombre'], 'detalle' => $detail, 'estado' => 'abierto',
            'detectado_at' => date('Y-m-d H:i:s', strtotime($detected)), 'conocimiento_at' => $knownAt, 'notificado_encargado_at' => $now,
            'sic_vence_at' => PrivacyBusinessDays::add(substr($knownAt, 0, 10), 15), 'titulares_estimados' => (int) $this->request->getPost('titulares_estimados'),
            'categorias_afectadas' => trim((string) $this->request->getPost('categorias')), 'decision_reporte' => 'pendiente', 'created_at' => $now, 'updated_at' => $now]));
        $incidentId = (int) $db->insertID();
        $db->table('dp_incidente_eventos')->insert((new PrivacyVault())->encryptRow('dp_incidente_eventos', ['incidente_id' => $incidentId, 'tipo' => 'notificacion_encargado',
            'detalle_json' => json_encode(['acuerdo_id' => $context['agreement']['id'], 'notificado_at' => $now], JSON_UNESCAPED_UNICODE),
            'evento_hash' => hash('sha256', $incidentId . '|notificacion_encargado|' . $now), 'usuario_id' => null, 'ocurrido_at' => $now, 'created_at' => $now]));
        $this->event((int) $context['agreement']['id'], 'incidente_remitido', ['incidente_id' => $incidentId, 'folio' => $folio]);
        return redirect()->back()->with('success', 'Incidente ' . $folio . ' abierto con cronometro SIC de 15 dias habiles.');
    }

    public function forwardRightsRequest(string $token)
    {
        $context = $this->context($token); $type = (string) $this->request->getPost('tipo'); $name = trim((string) $this->request->getPost('titular_nombre'));
        $email = trim((string) $this->request->getPost('titular_email')); $text = trim((string) $this->request->getPost('solicitud_texto'));
        if (! $context || $context['agreement']['estado'] !== 'vigente' || ! in_array($type, ['consulta','reclamo','rectificacion','actualizacion','revocatoria','supresion'], true)
            || $name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $text === '') { return redirect()->back()->with('error', 'Complete tipo, Titular, correo y solicitud recibida.'); }
        $db = db_connect(); $now = date('Y-m-d H:i:s'); $legalDate = PrivacyBusinessDays::legalReceipt($now); $isQuery = $type === 'consulta';
        $radicado = 'DP-E-' . date('Ymd') . '-' . $context['agreement']['cliente_id'] . '-' . strtoupper(bin2hex(random_bytes(3)));
        $db->table('dp_solicitudes')->insert((new PrivacyVault())->encryptRow('dp_solicitudes', ['cliente_id' => $context['agreement']['cliente_id'], 'tercero_origen_id' => $context['agreement']['tercero_id'],
            'acuerdo_encargado_id' => $context['agreement']['id'], 'radicado' => $radicado, 'tipo' => $type, 'clasificacion_original' => $type,
            'titular_nombre' => $name, 'titular_documento' => trim((string) $this->request->getPost('titular_documento')) ?: null, 'titular_email' => $email,
            'canal' => 'encargado', 'calidad_solicitante' => 'titular', 'solicitud_texto' => $text, 'estado' => 'recibida', 'identidad_estado' => 'pendiente',
            'recibida_at' => $now, 'fecha_ingreso_real' => $now, 'recibida_encargado_at' => date('Y-m-d H:i:s', strtotime((string) ($this->request->getPost('recibida_encargado_at') ?: $now))),
            'fecha_recepcion_legal' => $legalDate, 'vence_at' => $isQuery ? PrivacyBusinessDays::add($legalDate, 10) : null, 'created_at' => $now, 'updated_at' => $now]));
        $requestId = (int) $db->insertID();
        $db->table('dp_solicitud_eventos')->insert((new PrivacyVault())->encryptRow('dp_solicitud_eventos', ['solicitud_id' => $requestId, 'tipo' => 'remision_encargado',
            'detalle_json' => json_encode(['acuerdo_id' => $context['agreement']['id'], 'tercero' => $context['third']['nombre']], JSON_UNESCAPED_UNICODE),
            'evento_hash' => hash('sha256', $requestId . '|remision_encargado|' . $now), 'usuario_id' => null, 'ocurrido_at' => $now, 'created_at' => $now]));
        $this->event((int) $context['agreement']['id'], 'solicitud_titular_remitida', ['solicitud_id' => $requestId, 'radicado' => $radicado]);
        return redirect()->back()->with('success', 'Solicitud remitida con radicado ' . $radicado . '.');
    }

    private function context(string $token): ?array
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) { return null; }
        $db = db_connect(); $agreement = $db->table('dp_acuerdos_encargado')->where('token', $token)->get()->getRowArray();
        if (! $agreement || ! in_array($agreement['estado'], ['pendiente_firma', 'vigente'], true) || $agreement['vigencia_hasta'] < date('Y-m-d')) { return null; }
        $vault = new PrivacyVault(); $agreement = $vault->decryptRow('dp_acuerdos_encargado', $agreement);
        $client = $db->table('clientes')->where('id', $agreement['cliente_id'])->get()->getRowArray();
        $third = $db->table('dp_terceros')->where('id', $agreement['tercero_id'])->where('cliente_id', $agreement['cliente_id'])->get()->getRowArray();
        if ($third) { $third = $vault->decryptRow('dp_terceros', $third); }
        return $client && $third ? ['agreement' => $agreement, 'client' => $client, 'third' => $third, 'token' => $token] : null;
    }

    private function event(int $id, string $type, array $detail): void
    {
        $now = date('Y-m-d H:i:s'); $payload = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        db_connect()->table('dp_acuerdo_encargado_eventos')->insert((new PrivacyVault())->encryptRow('dp_acuerdo_encargado_eventos', ['acuerdo_id' => $id, 'tipo' => $type, 'detalle_json' => $payload,
            'evento_hash' => hash('sha256', $id . '|' . $type . '|' . $now . '|' . $payload), 'usuario_id' => null, 'ocurrido_at' => $now, 'created_at' => $now]));
    }

    private function signature(string $data): ?string
    {
        if (! preg_match('#^data:image/(png|jpeg);base64,([A-Za-z0-9+/=]+)$#', $data, $match)) { return null; }
        $binary = base64_decode($match[2], true); return $binary !== false && strlen($binary) <= 1024 * 1024 ? $data : null;
    }

    private function key(): string
    {
        $key = (string) (env('privacy.hashKey') ?: env('encryption.key')); if ($key === '') { throw new \RuntimeException('Falta clave de integridad.'); } return $key;
    }
}
