<?php

namespace App\Filters;

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

        $clientId = $this->targetClientId($request);
        $requiredInstruments = $this->requiredInstruments($request);
        if ($clientId > 0 && $requiredInstruments !== [] && ! $this->allowsAnyInstrument($clientId, $requiredInstruments)) {
            return redirect()->to($this->backUrl($clientId))->with('error', 'Este instrumento no esta habilitado para el cliente. La administracion de Cycloid debe activarlo segun el alcance del contrato SST.');
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
