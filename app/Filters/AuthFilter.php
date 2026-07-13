<?php

namespace App\Filters;

use App\Libraries\PrivacyAccessGate;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Debes iniciar sesion para continuar.');
        }
        $clientId = (int) session()->get('cliente_id');
        $userId = (int) session()->get('user_id');
        if ($clientId > 0 && $userId > 0 && ! (new PrivacyAccessGate())->ready($clientId, $userId)) {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'Tu acceso fue suspendido: verifica compromiso individual, vigencia e induccion.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
