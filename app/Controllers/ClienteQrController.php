<?php

namespace App\Controllers;

use App\Libraries\QrSvgService;
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

        return view('admin/clientes/qr/index', [
            'cliente'   => $cliente,
            'qrCodes'   => (new QrCodeModel())->forCliente((int) $cliente['id'])->orderBy('tipo_instrumento', 'ASC')->findAll(),
            'basePath'  => 'qr',
            'isAdminQr' => false,
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

        $titulo = trim((string) $this->request->getPost('titulo'));
        if ($titulo === '') {
            $titulo = $tipo === 'poblacional' ? 'Censo poblacional' : 'Censo de mascotas';
        }

        if ((new QrCodeModel())->forCliente($cid)->where('tipo_instrumento', $tipo)->first()) {
            return redirect()->back()->with('error', 'Ya existe un QR para ese instrumento.');
        }

        (new QrCodeModel())->insert([
            'cliente_id' => $cid, 'tipo_instrumento' => $tipo, 'token' => $this->uniqueToken(), 'titulo' => $titulo, 'activo' => 1,
        ]);

        return redirect()->back()->with('success', 'QR generado correctamente.');
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
        return (new QrCodeModel())->where('cliente_id', $clienteId)->where('id', $qrId)->first();
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
