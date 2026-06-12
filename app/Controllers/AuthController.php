<?php

namespace App\Controllers;

use App\Models\RolModel;
use App\Models\UsuarioModel;

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

        $usuarioModel = new UsuarioModel();
        $user         = $usuarioModel->where('email', $email)->first();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Credenciales invalidas.');
        }

        if ((int) $user['activo'] !== 1) {
            return redirect()->back()->withInput()->with('error', 'Tu cuenta esta inactiva. Contacta al administrador.');
        }

        $rol = (new RolModel())->find($user['rol_id']);

        session()->set([
            'isLoggedIn' => true,
            'user_id'    => $user['id'],
            'nombre'     => $user['nombre'],
            'email'      => $user['email'],
            'rol_id'     => $user['rol_id'],
            'rol'        => $rol['nombre'] ?? null,
            'cliente_id' => $user['cliente_id'],
        ]);

        $usuarioModel->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);

        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'Sesion cerrada correctamente.');
    }
}
