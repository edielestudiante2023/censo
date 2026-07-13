<?php

namespace App\Filters;

use App\Libraries\PrivacyAccessGate;
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

        $clientId = (int) session()->get('cliente_id');
        $userId = (int) session()->get('user_id');
        if ($clientId > 0 && $userId > 0) {
            $gate = new PrivacyAccessGate();
            if (! $gate->ready($clientId, $userId)) {
                session()->destroy();
                return redirect()->to('/login')->with('error', 'Tu acceso fue suspendido: verifica compromiso individual, vigencia e induccion.');
            }
            if ($this->requiresPrivacyGovernance($request) && ! $gate->allowsPrivacyGovernance($clientId, $userId)) {
                return redirect()->to('/dashboard')->with('error', 'Tu rol operativo no autoriza la administracion del programa de datos personales.');
            }
            $operation = $this->requiredOperation($request);
            if ($operation !== null && ! $gate->allowsOperation($clientId, $userId, $operation)) {
                return redirect()->to('/dashboard')->with('error', 'La operacion no figura en tu compromiso individual vigente.');
            }
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

    private function requiredOperation(RequestInterface $request): ?string
    {
        $path = trim($request->getUri()->getPath(), '/');
        if (preg_match('#(?:respuestas|inteligencia|datos-personales)/(?:exportar|excel|completo)|datos-personales/(?:consentimientos|solicitudes)/pdf#', $path)) {
            return 'exportar';
        }
        if ($request->getMethod() === 'GET' && preg_match('#(?:tablero|respuestas|inteligencia|datos-personales|qr)(?:/|$)#', $path)) {
            return 'consultar';
        }
        if ($request->getMethod() !== 'POST') {
            return null;
        }
        if (str_contains($path, 'solicitudes/base')) {
            return match ((string) $request->getPost('accion')) {
                'suprimir' => 'suprimir',
                'rectificar', 'actualizar', 'anonimizar', 'bloquear' => 'actualizar',
                default => 'consultar',
            };
        }
        if (str_contains($path, 'datos-personales') || str_contains($path, '/qr')) {
            return preg_match('#/(archivar|cerrar|actualizar|regenerate)(?:/|$)#', $path) ? 'actualizar' : 'registrar';
        }
        return null;
    }

    private function requiresPrivacyGovernance(RequestInterface $request): bool
    {
        return $request->getMethod() === 'POST' && str_contains(trim($request->getUri()->getPath(), '/'), 'datos-personales');
    }
}
