<?php

namespace App\Controllers;

use App\Libraries\EmailService;
use App\Models\RolModel;
use App\Models\UsuarioModel;
use App\Libraries\PrivacyAccessGate;
use App\Libraries\PrivacyVault;

class AuthController extends BaseController
{
    public function index()
    {
        return redirect()->to(session()->get('isLoggedIn') ? '/dashboard' : '/login');
    }

    public function login()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    public function attemptLogin()
    {
        $email    = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');

        if ($email === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'Ingresa correo y contrasena.');
        }

        // A-2: limita fuerza bruta por IP y por cuenta objetivo sin revelar existencia.
        $throttler = service('throttler');
        $ip = $this->request->getIPAddress();
        if ($throttler->check('login-ip-' . $ip, 10, MINUTE) === false
            || $throttler->check('login-acct-' . hash('sha256', mb_strtolower($email)), 5, 5 * MINUTE) === false) {
            return redirect()->back()->withInput()->with('error', 'Demasiados intentos. Espera unos minutos e intenta de nuevo.');
        }

        $usuarioModel = new UsuarioModel();
        $user         = $usuarioModel->where('email', $email)->first();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Credenciales invalidas.');
        }

        if ((int) $user['activo'] !== 1) {
            return redirect()->back()->withInput()->with('error', 'Tu cuenta esta inactiva. Contacta al administrador.');
        }

        $rol = (new RolModel())->find($user['rol_id']);

        $roleName = (string) ($rol['nombre'] ?? '');
        $accessGate = new PrivacyAccessGate();
        if (! empty($user['cliente_id']) && ! $accessGate->ready((int) $user['cliente_id'], (int) $user['id'])) {
            return redirect()->back()->withInput()->with('error', 'Tu acceso requiere un compromiso individual vigente. Contacta al administrador.');
        }
        $privacyMfa = ! empty($user['cliente_id']) && $accessGate->requiresMfa((int) $user['cliente_id'], (int) $user['id']);
        if (in_array($roleName, ['superadmin', 'admin', 'cliente'], true) || $privacyMfa) {
            return $this->startMfa($user);
        }

        return $this->completeLogin($user, $roleName);
    }

    public function mfa()
    {
        $challengeId = (int) session()->get('pending_mfa_challenge');
        $userId = (int) session()->get('pending_mfa_user');
        $challenge = $challengeId ? db_connect()->table('usuario_mfa_desafios')->where('id', $challengeId)->where('usuario_id', $userId)
            ->where('consumido_at', null)->where('expira_at >=', date('Y-m-d H:i:s'))->get()->getRowArray() : null;
        if (! $challenge) {
            session()->remove(['pending_mfa_challenge', 'pending_mfa_user']);
            return redirect()->to('/login')->with('error', 'El codigo de verificacion expiro. Inicia sesion nuevamente.');
        }
        $user = (new UsuarioModel())->find($userId);
        return view('auth/mfa', ['maskedEmail' => $this->maskEmail((string) ($user['email'] ?? ''))]);
    }

    public function verifyMfa()
    {
        $challengeId = (int) session()->get('pending_mfa_challenge');
        $userId = (int) session()->get('pending_mfa_user');
        $code = preg_replace('/\D/', '', (string) $this->request->getPost('codigo'));
        $db = db_connect();
        $challenge = $challengeId ? $db->table('usuario_mfa_desafios')->where('id', $challengeId)->where('usuario_id', $userId)
            ->where('consumido_at', null)->get()->getRowArray() : null;
        if (! $challenge || $challenge['expira_at'] < date('Y-m-d H:i:s') || (int) $challenge['intentos'] >= 5) {
            session()->remove(['pending_mfa_challenge', 'pending_mfa_user']);
            return redirect()->to('/login')->with('error', 'El desafio de verificacion no es valido.');
        }
        $expected = hash_hmac('sha256', $code, $this->mfaKey());
        if (strlen($code) !== 6 || ! hash_equals((string) $challenge['codigo_hash'], $expected)) {
            $db->table('usuario_mfa_desafios')->where('id', $challengeId)->update(['intentos' => (int) $challenge['intentos'] + 1]);
            return redirect()->back()->with('error', 'Codigo invalido.');
        }
        $now = date('Y-m-d H:i:s');
        $db->table('usuario_mfa_desafios')->where('id', $challengeId)->update(['verificado_at' => $now, 'consumido_at' => $now]);
        session()->remove(['pending_mfa_challenge', 'pending_mfa_user']);
        $user = (new UsuarioModel())->find($userId);
        if (! $user || (int) $user['activo'] !== 1) { return redirect()->to('/login')->with('error', 'Cuenta no disponible.'); }
        if (! empty($user['cliente_id']) && ! (new PrivacyAccessGate())->ready((int) $user['cliente_id'], (int) $user['id'])) {
            return redirect()->to('/login')->with('error', 'El compromiso individual dejo de estar vigente.');
        }
        $role = (new RolModel())->find($user['rol_id']);
        return $this->completeLogin($user, (string) ($role['nombre'] ?? ''));
    }

    private function startMfa(array $user)
    {
        $code = (string) random_int(100000, 999999);
        $now = date('Y-m-d H:i:s');
        $db = db_connect();
        $db->table('usuario_mfa_desafios')->insert((new PrivacyVault())->encryptRow('usuario_mfa_desafios', ['usuario_id' => $user['id'],
            'codigo_hash' => hash_hmac('sha256', $code, $this->mfaKey()), 'intentos' => 0,
            'expira_at' => date('Y-m-d H:i:s', strtotime('+10 minutes')), 'ip' => $this->request->getIPAddress(), 'created_at' => $now]));
        $challengeId = (int) $db->insertID();
        $html = '<p>Tu codigo de verificacion para Censo APP es:</p><p style="font-size:24px;font-weight:bold;letter-spacing:4px">' . $code . '</p><p>Expira en 10 minutos. Si no intentaste iniciar sesion, informa al administrador.</p>';
        $result = (new EmailService())->sendMfaCode((string) $user['email'], 'Codigo de verificacion - Censo APP', $html);
        if (! $result['success']) {
            $db->table('usuario_mfa_desafios')->where('id', $challengeId)->update(['consumido_at' => $now]);
            return redirect()->to('/login')->with('error', 'No fue posible enviar el segundo factor. Contacta al administrador.');
        }
        session()->set(['pending_mfa_challenge' => $challengeId, 'pending_mfa_user' => $user['id']]);
        return redirect()->to('/login/verificar');
    }

    private function completeLogin(array $user, string $roleName)
    {
        session()->set([
            'isLoggedIn' => true,
            'user_id'    => $user['id'],
            'nombre'     => $user['nombre'],
            'email'      => $user['email'],
            'rol_id'     => $user['rol_id'],
            'rol'        => $roleName,
            'cliente_id' => $user['cliente_id'],
        ]);

        (new UsuarioModel())->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);

        return redirect()->to('/dashboard');
    }

    private function mfaKey(): string
    {
        $key = (string) (env('privacy.hashKey') ?: env('encryption.key'));
        if ($key === '') { throw new \RuntimeException('No existe clave de integridad para MFA.'); }
        return $key;
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        return substr($local, 0, 2) . str_repeat('*', max(2, strlen($local) - 2)) . '@' . $domain;
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'Sesion cerrada correctamente.');
    }
}
