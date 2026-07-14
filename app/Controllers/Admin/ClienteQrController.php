<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\QrSvgService;
use App\Libraries\ClientInstrumentAccess;
use App\Models\ClienteModel;
use App\Models\QrCodeModel;

class ClienteQrController extends BaseController
{
    private const TIPOS = ['poblacional', 'mascotas'];

    public function index(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        $instrumentos = (new ClientInstrumentAccess())->enabledMap($clienteId);
        $qrCodes = array_values(array_filter((new QrCodeModel())->forCliente($clienteId)->orderBy('tipo_instrumento', 'ASC')->findAll(),
            static fn (array $qr): bool => ! empty($instrumentos[$qr['tipo_instrumento'] === 'poblacional' ? ClientInstrumentAccess::POBLACIONAL : ClientInstrumentAccess::MASCOTAS])));
        return view('admin/clientes/qr/index', [
            'cliente'   => $cliente,
            'qrCodes'   => $qrCodes,
            'basePath'  => 'admin/clientes/' . $clienteId . '/qr',
            'isAdminQr' => true,
            'instrumentos' => $instrumentos,
        ]);
    }

    public function create(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        $tipo = (string) $this->request->getPost('tipo_instrumento');
        if (! in_array($tipo, self::TIPOS, true)) {
            return redirect()->back()->with('error', 'Selecciona un instrumento valido.');
        }
        $entitlement = $tipo === 'poblacional' ? ClientInstrumentAccess::POBLACIONAL : ClientInstrumentAccess::MASCOTAS;
        if (! (new ClientInstrumentAccess())->enabled($clienteId, $entitlement)) {
            return redirect()->back()->with('error', 'Ese instrumento no esta habilitado para el cliente.');
        }

        $anio = (int) ($this->request->getPost('anio') ?: date('Y'));
        if ($anio < 2020 || $anio > 2100) {
            $anio = (int) date('Y');
        }

        $titulo = trim((string) $this->request->getPost('titulo'));
        if ($titulo === '') {
            $titulo = ($tipo === 'poblacional' ? 'Censo poblacional' : 'Censo de mascotas') . ' ' . $anio;
        }

        $existing = (new QrCodeModel())
            ->forCliente($clienteId)
            ->where('tipo_instrumento', $tipo)
            ->where('anio', $anio)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', "Ya existe un QR de {$tipo} para el ano {$anio}.");
        }

        // Campana nueva: desactivar el QR activo anterior de ese instrumento (queda historico).
        db_connect()->table('qr_codes')
            ->where('cliente_id', $clienteId)->where('tipo_instrumento', $tipo)
            ->update(['activo' => 0, 'updated_at' => date('Y-m-d H:i:s')]);

        (new QrCodeModel())->insert([
            'cliente_id' => $clienteId,
            'tipo_instrumento' => $tipo,
            'anio' => $anio,
            'token' => $this->uniqueToken(),
            'titulo' => $titulo,
            'activo' => 1,
        ]);

        return redirect()->back()->with('success', "QR de la campana {$anio} generado. El QR anterior quedo inactivo.");
    }

    public function update(int $clienteId, int $qrId)
    {
        $qr = $this->findQr($clienteId, $qrId);
        if (! $qr) {
            return redirect()->back()->with('error', 'QR no encontrado.');
        }

        $titulo = trim((string) $this->request->getPost('titulo'));
        (new QrCodeModel())->update($qrId, [
            'titulo' => $titulo !== '' ? $titulo : null,
            'activo' => $this->request->getPost('activo') ? 1 : 0,
        ]);

        return redirect()->back()->with('success', 'QR actualizado correctamente.');
    }

    public function regenerate(int $clienteId, int $qrId)
    {
        $qr = $this->findQr($clienteId, $qrId);
        if (! $qr) {
            return redirect()->back()->with('error', 'QR no encontrado.');
        }

        (new QrCodeModel())->update($qrId, ['token' => $this->uniqueToken()]);

        return redirect()->back()->with('success', 'Token regenerado correctamente.');
    }

    public function svg(int $clienteId, int $qrId)
    {
        $cliente = $this->findCliente($clienteId);
        $qr      = $this->findQr($clienteId, $qrId);

        if (! $cliente || ! $qr) {
            return redirect()->to('/admin/clientes')->with('error', 'QR no encontrado.');
        }

        $svg = (new QrSvgService())->render($this->publicUrl($qr['token']), $cliente['color_primario'] ?? '#111827');

        return $this->response
            ->setHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->setHeader('Content-Disposition', 'inline; filename="qr-' . $cliente['slug'] . '-' . $qr['tipo_instrumento'] . '.svg"')
            ->setBody($svg);
    }

    public function pieza(int $clienteId, int $qrId)
    {
        $cliente = $this->findCliente($clienteId);
        $qr      = $this->findQr($clienteId, $qrId);

        if (! $cliente || ! $qr) {
            return redirect()->to('/admin/clientes')->with('error', 'QR no encontrado.');
        }

        return view('admin/clientes/qr/pieza', [
            'cliente' => $cliente,
            'qr' => $qr,
            'url' => $this->publicUrl($qr['token']),
            'qrSvg' => (new QrSvgService())->render($this->publicUrl($qr['token']), $cliente['color_primario'] ?? '#111827', 420),
        ]);
    }

    private function findCliente(int $clienteId): ?array
    {
        return (new ClienteModel())->find($clienteId);
    }

    private function findQr(int $clienteId, int $qrId): ?array
    {
        $qr = (new QrCodeModel())
            ->where('cliente_id', $clienteId)
            ->where('id', $qrId)
            ->first();
        if (! $qr) {
            return null;
        }
        $instrument = $qr['tipo_instrumento'] === 'poblacional' ? ClientInstrumentAccess::POBLACIONAL : ClientInstrumentAccess::MASCOTAS;

        return (new ClientInstrumentAccess())->enabled($clienteId, $instrument) ? $qr : null;
    }

    private function publicUrl(string $token): string
    {
        return base_url('q/' . $token);
    }

    private function uniqueToken(): string
    {
        $model = new QrCodeModel();

        do {
            $token = bin2hex(random_bytes(24));
        } while ($model->withDeleted()->where('token', $token)->first());

        return $token;
    }
}
