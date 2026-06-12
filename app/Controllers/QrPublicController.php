<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\QrCodeModel;

class QrPublicController extends BaseController
{
    public function resolve(string $token)
    {
        $qr = (new QrCodeModel())->findByToken($token);
        if (! $qr) {
            return view('public/qr_not_found');
        }

        $cliente = (new ClienteModel())->find((int) $qr['cliente_id']);
        if (! $cliente || (int) $cliente['activo'] !== 1) {
            return view('public/qr_not_found');
        }

        return view('public/qr_pending', [
            'cliente' => $cliente,
            'qr' => $qr,
        ]);
    }
}
