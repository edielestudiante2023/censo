<?php

namespace App\Controllers;

use App\Libraries\EmailService;
use App\Libraries\PrivacyConfidentialityService;
use App\Libraries\PrivacyPdf;
use App\Libraries\PrivacyVault;

final class PrivacyConfidentialityController extends BaseController
{
    public function show(string $token)
    {
        $context = $this->context($token);
        if (! $context) { return view('privacy/public_not_found'); }
        if ($context['agreement']['estado'] === 'vigente') {
            return view('privacy/confidentiality_portal', $context + ['success' => true, 'confirmation' => 'Este compromiso ya fue aceptado y se encuentra vigente.']);
        }
        return view('privacy/confidentiality_portal', $context + ['success' => false]);
    }

    public function confirmView(string $token)
    {
        $context = $this->context($token);
        if (! $context || $context['agreement']['estado'] !== 'pendiente') { return view('privacy/public_not_found'); }
        if (empty($context['agreement']['vista_at'])) {
            $now = date('Y-m-d H:i:s');
            db_connect()->table('dp_compromisos_confidencialidad')->where('id', $context['agreement']['id'])->update(['vista_at' => $now, 'updated_at' => $now]);
            $this->event((int) $context['agreement']['id'], 'visualizacion_completa', ['ip' => $this->request->getIPAddress(), 'acto' => 'confirmacion_tras_scroll']);
        }
        return redirect()->back()->with('success', 'Lectura completa registrada. Ya puedes solicitar el codigo y firmar.');
    }

    public function sendCode(string $token)
    {
        $context = $this->context($token);
        if (! $context || $context['agreement']['estado'] !== 'pendiente') { return view('privacy/public_not_found'); }
        if (service('throttler')->check('conf-code-' . $context['agreement']['id'] . '-' . $this->request->getIPAddress(), 5, 10 * MINUTE) === false) {
            return redirect()->back()->with('error', 'Se enviaron demasiados codigos. Espera unos minutos antes de solicitar otro.');
        }
        $code = (string) random_int(100000, 999999); $now = date('Y-m-d H:i:s');
        db_connect()->table('dp_compromisos_confidencialidad')->where('id', $context['agreement']['id'])->update([
            'codigo_hash' => hash_hmac('sha256', $code, $this->key()), 'codigo_expira_at' => date('Y-m-d H:i:s', strtotime('+10 minutes')),
            'codigo_intentos' => 0, 'codigo_verificado_at' => null, 'updated_at' => $now]);
        $html = '<p>Tu codigo para firmar el compromiso individual es:</p><p style="font-size:24px;font-weight:bold;letter-spacing:4px">' . $code . '</p><p>Expira en 10 minutos.</p>';
        $result = (new EmailService())->sendPrivacyMessage($context['user']['email'], 'Codigo para compromiso de confidencialidad', $html, null, (int) $context['client']['id']);
        $this->event((int) $context['agreement']['id'], 'codigo_solicitado', ['envio_aceptado' => $result['success']]);
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['success'] ? 'Codigo enviado.' : 'No fue posible enviar el codigo.');
    }

    public function accept(string $token)
    {
        $context = $this->context($token); $db = db_connect();
        if (! $context || $context['agreement']['estado'] !== 'pendiente') { return view('privacy/public_not_found'); }
        $agreement = $context['agreement']; $code = preg_replace('/\D/', '', (string) $this->request->getPost('codigo'));
        if (empty($agreement['vista_at']) || empty($agreement['codigo_hash']) || $agreement['codigo_expira_at'] < date('Y-m-d H:i:s') || (int) $agreement['codigo_intentos'] >= 5) {
            return redirect()->back()->with('error', 'Debes visualizar la instancia y solicitar un codigo vigente.');
        }
        if (strlen($code) !== 6 || ! hash_equals((string) $agreement['codigo_hash'], hash_hmac('sha256', $code, $this->key()))) {
            $db->table('dp_compromisos_confidencialidad')->where('id', $agreement['id'])->update(['codigo_intentos' => (int) $agreement['codigo_intentos'] + 1]);
            return redirect()->back()->with('error', 'Codigo invalido.');
        }
        if (! (new PrivacyConfidentialityService())->verify((string) $agreement['instancia_html'], (string) $agreement['instancia_hash'])) {
            $this->event((int) $agreement['id'], 'falla_integridad', ['hash_esperado' => $agreement['instancia_hash']]);
            return redirect()->back()->with('error', 'COPIA NO VALIDA: la instancia no supera la verificacion de integridad.');
        }
        $master = $db->table('dp_documentos')->where('cliente_id', $agreement['cliente_id'])->where('tipo', 'confidencialidad')
            ->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
        if (! $master || (int) $agreement['documento_id'] !== (int) $master['id']
            || ! hash_equals((string) $master['hash_sha256'], hash('sha256', (string) $master['contenido_html']))
            || ! hash_equals((string) $agreement['documento_hash'], (string) $master['hash_sha256'])) {
            return redirect()->back()->with('error', 'La version maestra cambio; solicita una nueva instancia.');
        }
        $signature = $this->signature((string) $this->request->getPost('firma_imagen'));
        if (! $signature || ! $this->request->getPost('acepto')) { return redirect()->back()->with('error', 'La aceptacion expresa y la firma son obligatorias.'); }
        $now = date('Y-m-d H:i:s');
        foreach ($db->table('dp_compromisos_confidencialidad')->where('cliente_id', $agreement['cliente_id'])->where('usuario_id', $agreement['usuario_id'])->where('estado', 'vigente')->get()->getResultArray() as $previous) {
            $db->table('dp_compromisos_confidencialidad')->where('id', $previous['id'])->update((new PrivacyVault())->encryptRow('dp_compromisos_confidencialidad', ['estado' => 'superado', 'cerrado_at' => $now, 'cierre_motivo' => 'Nueva instancia aceptada', 'updated_at' => $now]));
            $this->event((int) $previous['id'], 'superado', ['nueva_instancia_id' => $agreement['id']]);
        }
        $db->table('dp_compromisos_confidencialidad')->where('id', $agreement['id'])->update((new PrivacyVault())->encryptRow('dp_compromisos_confidencialidad', ['codigo_verificado_at' => $now,
            'firma_imagen' => $signature, 'firma_hash' => hash('sha256', $signature), 'aceptado_at' => $now,
            'ip' => $this->request->getIPAddress(), 'user_agent' => substr((string) $this->request->getUserAgent(), 0, 500),
            'estado' => 'vigente', 'updated_at' => $now]));
        $this->event((int) $agreement['id'], 'aceptacion', ['instancia_hash' => $agreement['instancia_hash'], 'firma_hash' => hash('sha256', $signature), 'canal' => 'otp_y_firma_electronica']);
        $compliance = $db->table('dp_usuario_privacidad')->where('cliente_id', $agreement['cliente_id'])->where('usuario_id', $agreement['usuario_id'])->get()->getRowArray();
        $complianceData = ['confidencialidad_at' => $now, 'confidencialidad_hash' => $agreement['instancia_hash'], 'recertificado_at' => $now, 'updated_at' => $now];
        if ($compliance) { $db->table('dp_usuario_privacidad')->where('id', $compliance['id'])->update($complianceData); }
        else { $complianceData += ['cliente_id' => $agreement['cliente_id'], 'usuario_id' => $agreement['usuario_id'], 'created_at' => $now]; $db->table('dp_usuario_privacidad')->insert($complianceData); }
        $agreement = array_merge($agreement, ['firma_imagen' => $signature, 'aceptado_at' => $now]);
        $path = (new PrivacyPdf())->confidentiality($agreement, $context['client']);
        $result = (new EmailService())->sendPrivacyMessage($context['user']['email'], 'Copia de compromiso individual de confidencialidad', '<p>Adjuntamos la instancia individual aceptada y sellada.</p>', WRITEPATH . $path, (int) $context['client']['id']);
        if ($result['success']) { $db->table('dp_compromisos_confidencialidad')->where('id', $agreement['id'])->update(['copia_enviada_at' => $now]); }
        return view('privacy/confidentiality_portal', $context + ['success' => true, 'confirmation' => 'Compromiso aceptado y sellado.']);
    }

    private function context(string $token): ?array
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) { return null; }
        $db = db_connect(); $agreement = $db->table('dp_compromisos_confidencialidad')->where('token', $token)->get()->getRowArray();
        if (! $agreement || ! in_array($agreement['estado'], ['pendiente', 'vigente'], true) || $agreement['vigencia_hasta'] < date('Y-m-d')) { return null; }
        $agreement = (new PrivacyVault())->decryptRow('dp_compromisos_confidencialidad', $agreement);
        $client = $db->table('clientes')->where('id', $agreement['cliente_id'])->get()->getRowArray();
        $user = $db->table('usuarios')->where('id', $agreement['usuario_id'])->where('cliente_id', $agreement['cliente_id'])->get()->getRowArray();
        return $client && $user ? ['agreement' => $agreement, 'client' => $client, 'user' => $user, 'token' => $token] : null;
    }

    private function event(int $agreementId, string $type, array $detail): void
    {
        $now = date('Y-m-d H:i:s'); $payload = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        db_connect()->table('dp_compromiso_eventos')->insert((new PrivacyVault())->encryptRow('dp_compromiso_eventos', ['compromiso_id' => $agreementId, 'tipo' => $type,
            'detalle_json' => $payload, 'evento_hash' => hash('sha256', $agreementId . '|' . $type . '|' . $now . '|' . $payload),
            'usuario_id' => null, 'ocurrido_at' => $now, 'created_at' => $now]));
    }

    private function signature(string $data): ?string
    {
        if (! preg_match('#^data:image/(png|jpeg);base64,([A-Za-z0-9+/=]+)$#', $data, $match)) { return null; }
        $binary = base64_decode($match[2], true);
        return $binary !== false && strlen($binary) <= 1024 * 1024 ? $data : null;
    }

    private function key(): string
    {
        $key = (string) (env('privacy.hashKey') ?: env('encryption.key'));
        if ($key === '') { throw new \RuntimeException('Falta clave de integridad.'); }
        return $key;
    }
}
