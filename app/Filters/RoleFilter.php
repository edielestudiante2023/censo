<?php

namespace App\Filters;

use App\Libraries\PrivacyAccessGate;
use App\Libraries\ClientInstrumentAccess;
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

        $sessionClientId = (int) session()->get('cliente_id');
        $clientId = $this->targetClientId($request);
        $userId = (int) session()->get('user_id');
        $requiredInstruments = $this->requiredInstruments($request);
        if ($clientId > 0 && $requiredInstruments !== [] && ! $this->allowsAnyInstrument($clientId, $requiredInstruments)) {
            return redirect()->to($this->backUrl($clientId))->with('error', 'Este instrumento no esta habilitado para el cliente. La administracion de Cycloid debe activarlo segun el alcance del contrato SST.');
        }
        if ($sessionClientId > 0 && $clientId === $sessionClientId && $userId > 0
            && in_array(ClientInstrumentAccess::DATOS_PERSONALES, $requiredInstruments, true)) {
            $gate = new PrivacyAccessGate();
            if (! $gate->ready($clientId, $userId)) {
                session()->destroy();
                return redirect()->to('/login')->with('error', 'Tu acceso fue suspendido: verifica el compromiso individual y su vigencia.');
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

    private function targetClientId(RequestInterface $request): int
    {
        $path = trim($request->getUri()->getPath(), '/');
        if (preg_match('#^admin/clientes/(\d+)(?:/|$)#', $path, $match)) {
            return (int) $match[1];
        }

        return (int) session()->get('cliente_id');
    }

    private function requiredInstruments(RequestInterface $request): array
    {
        $path = trim($request->getUri()->getPath(), '/');
        if (str_contains($path, 'datos-personales')) {
            return [ClientInstrumentAccess::DATOS_PERSONALES];
        }
        if (str_contains($path, 'inteligencia')) {
            return [ClientInstrumentAccess::POBLACIONAL];
        }
        if (! preg_match('#(?:^|/)(tablero|respuestas|qr|config)(?:/|$)#', $path, $sectionMatch)) {
            return [];
        }
        if ($sectionMatch[1] === 'config') {
            return [ClientInstrumentAccess::POBLACIONAL, ClientInstrumentAccess::MASCOTAS, ClientInstrumentAccess::DATOS_PERSONALES];
        }
        if (preg_match('#/pdf/(poblacional|mascotas)/#', $path, $match)) {
            return [$match[1] === 'poblacional' ? ClientInstrumentAccess::POBLACIONAL : ClientInstrumentAccess::MASCOTAS];
        }
        $requested = (string) ($request->getPost('tipo_instrumento') ?: $request->getGet('instrumento'));
        if (in_array($requested, ['poblacional', 'mascotas'], true)) {
            return [$requested === 'poblacional' ? ClientInstrumentAccess::POBLACIONAL : ClientInstrumentAccess::MASCOTAS];
        }

        return [ClientInstrumentAccess::POBLACIONAL, ClientInstrumentAccess::MASCOTAS];
    }

    private function allowsAnyInstrument(int $clientId, array $instruments): bool
    {
        $access = new ClientInstrumentAccess();
        foreach ($instruments as $instrument) {
            if ($access->enabled($clientId, $instrument)) {
                return true;
            }
        }

        return false;
    }

    private function backUrl(int $clientId): string
    {
        return in_array((string) session()->get('rol'), ['superadmin', 'admin'], true)
            ? '/admin/clientes/' . $clientId
            : '/dashboard';
    }
}
