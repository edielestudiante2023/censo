<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    /**
     * Uso en rutas: ['filter' => 'role:superadmin,admin']
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Debes iniciar sesion para continuar.');
        }

        if (! empty($arguments)) {
            $rol = session()->get('rol');
            if (! in_array($rol, $arguments, true)) {
                return redirect()->to('/dashboard')->with('error', 'No tienes permiso para acceder a esa seccion.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
