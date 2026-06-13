<?php

namespace App\Controllers;

use App\Libraries\EmailService;
use App\Models\UsuarioModel;

class PasswordController extends BaseController
{
    public function forgot()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/forgot');
    }

    public function sendLink()
    {
        $email = trim((string) $this->request->getPost('email'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Ingresa un correo valido.');
        }

        $model = new UsuarioModel();
        $user  = $model->where('email', $email)->where('activo', 1)->first();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $model->update($user['id'], [
                'reset_token'   => hash('sha256', $token),
                'reset_expires' => date('Y-m-d H:i:s', time() + 3600),
            ]);
            (new EmailService())->sendPasswordReset($user['email'], $user['nombre'], base_url('reset/' . $token));
        }

        // Mensaje generico: no revela si el correo existe.
        return redirect()->to('/login')->with('success', 'Si el correo esta registrado, te enviamos un enlace para restablecer la contrasena.');
    }

    public function reset(string $token)
    {
        $user = (new UsuarioModel())->findByValidResetToken(hash('sha256', $token));
        if (! $user) {
            return redirect()->to('/forgot')->with('error', 'El enlace es invalido o expiro. Solicita uno nuevo.');
        }

        return view('auth/reset', ['token' => $token]);
    }

    public function update(string $token)
    {
        $model = new UsuarioModel();
        $user  = $model->findByValidResetToken(hash('sha256', $token));
        if (! $user) {
            return redirect()->to('/forgot')->with('error', 'El enlace es invalido o expiro. Solicita uno nuevo.');
        }

        $password = (string) $this->request->getPost('password');
        $confirm  = (string) $this->request->getPost('password_confirm');

        if (strlen($password) < 8) {
            return redirect()->back()->with('error', 'La contrasena debe tener al menos 8 caracteres.');
        }
        if ($password !== $confirm) {
            return redirect()->back()->with('error', 'Las contrasenas no coinciden.');
        }

        $model->update($user['id'], [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'reset_token'   => null,
            'reset_expires' => null,
        ]);

        return redirect()->to('/login')->with('success', 'Contrasena actualizada. Ya puedes iniciar sesion.');
    }
}
