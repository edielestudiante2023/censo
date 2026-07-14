<?php

namespace App\Controllers;

use App\Libraries\QrSvgService;
use App\Libraries\ClientInstrumentAccess;
use App\Models\ClienteModel;
use App\Models\QrCodeModel;

/**
 * QR para los roles del conjunto (cliente/consejo/comite): operan sobre su propio cliente_id.
 */
class ClienteQrController extends BaseController
{
    private const TIPOS = ['poblacional', 'mascotas'];

    private function cliente(): ?array
    {
        $cid = session()->get('cliente_id');

        return $cid ? (new ClienteModel())->find((int) $cid) : null;
    }

    public function mine()
    {
        $cliente = $this->cliente();
        if (! $cliente) {
            return redirect()->to('/dashboard')->with('error', 'Tu usuario no tiene un conjunto asignado.');
        }

        $instrumentos = (new ClientInstrumentAccess())->enabledMap((int) $cliente['id']);
        $qrCodes = array_values(array_filter((new QrCodeModel())->forCliente((int) $cliente['id'])->orderBy('tipo_instrumento', 'ASC')->findAll(),
            static fn (array $qr): bool => ! empty($instrumentos[$qr['tipo_instrumento'] === 'poblacional' ? ClientInstrumentAccess::POBLACIONAL : ClientInstrumentAccess::MASCOTAS])));
        return view('admin/clientes/qr/index', [
            'cliente'   => $cliente,
            'qrCodes'   => $qrCodes,
            'basePath'  => 'qr',
            'isAdminQr' => false,
            'instrumentos' => $instrumentos,
        ]);
    }

    public function create()
    {
        $cliente = $this->cliente();
        if (! $cliente) {
            return redirect()->to('/dashboard')->with('error', 'Tu usuario no tiene un conjunto asignado.');
        }
        $cid = (int) $cliente['id'];

        $tipo = (string) $this->request->getPost('tipo_instrumento');
        if (! in_array($tipo, self::TIPOS, true)) {
            return redirect()->back()->with('error', 'Selecciona un instrumento valido.');
        }
        $entitlement = $tipo === 'poblacional' ? ClientInstrumentAccess::POBLACIONAL : ClientInstrumentAccess::MASCOTAS;
        if (! (new ClientInstrumentAccess())->enabled($cid, $entitlement)) {
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

        if ((new QrCodeModel())->forCliente($cid)->where('tipo_instrumento', $tipo)->where('anio', $anio)->first()) {
            return redirect()->back()->with('error', "Ya existe un QR de {$tipo} para el ano {$anio}.");
        }

        db_connect()->table('qr_codes')
            ->where('cliente_id', $cid)->where('tipo_instrumento', $tipo)
            ->update(['activo' => 0, 'updated_at' => date('Y-m-d H:i:s')]);

        (new QrCodeModel())->insert([
            'cliente_id' => $cid, 'tipo_instrumento' => $tipo, 'anio' => $anio, 'token' => $this->uniqueToken(), 'titulo' => $titulo, 'activo' => 1,
        ]);

        return redirect()->back()->with('success', "QR de la campana {$anio} generado. El QR anterior quedo inactivo.");
    }

    public function update(int $qrId)
    {
        $cliente = $this->cliente();
        $qr      = $cliente ? $this->findQr((int) $cliente['id'], $qrId) : null;
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

    public function regenerate(int $qrId)
    {
        $cliente = $this->cliente();
        $qr      = $cliente ? $this->findQr((int) $cliente['id'], $qrId) : null;
        if (! $qr) {
            return redirect()->back()->with('error', 'QR no encontrado.');
        }

        (new QrCodeModel())->update($qrId, ['token' => $this->uniqueToken()]);

        return redirect()->back()->with('success', 'Token regenerado correctamente.');
    }

    public function svg(int $qrId)
    {
        $cliente = $this->cliente();
        $qr      = $cliente ? $this->findQr((int) $cliente['id'], $qrId) : null;
        if (! $cliente || ! $qr) {
            return redirect()->to('/qr')->with('error', 'QR no encontrado.');
        }

        $svg = (new QrSvgService())->render(base_url('q/' . $qr['token']), $cliente['color_primario'] ?? '#111827');

        return $this->response
            ->setHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->setHeader('Content-Disposition', 'inline; filename="qr-' . $cliente['slug'] . '-' . $qr['tipo_instrumento'] . '.svg"')
            ->setBody($svg);
    }

    public function pieza(int $qrId)
    {
        $cliente = $this->cliente();
        $qr      = $cliente ? $this->findQr((int) $cliente['id'], $qrId) : null;
        if (! $cliente || ! $qr) {
            return redirect()->to('/qr')->with('error', 'QR no encontrado.');
        }

        return view('admin/clientes/qr/pieza', [
            'cliente' => $cliente,
            'qr'      => $qr,
            'url'     => base_url('q/' . $qr['token']),
            'qrSvg'   => (new QrSvgService())->render(base_url('q/' . $qr['token']), $cliente['color_primario'] ?? '#111827', 420),
        ]);
    }

    private function findQr(int $clienteId, int $qrId): ?array
    {
        $qr = (new QrCodeModel())->where('cliente_id', $clienteId)->where('id', $qrId)->first();
        if (! $qr) {
            return null;
        }
        $instrument = $qr['tipo_instrumento'] === 'poblacional' ? ClientInstrumentAccess::POBLACIONAL : ClientInstrumentAccess::MASCOTAS;

        return (new ClientInstrumentAccess())->enabled($clienteId, $instrument) ? $qr : null;
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
