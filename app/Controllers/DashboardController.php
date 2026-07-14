<?php

namespace App\Controllers;

use App\Libraries\ClientInstrumentAccess;

class DashboardController extends BaseController
{
    public function index()
    {
        $clientId = (int) session()->get('cliente_id');
        return view('dashboard/index', [
            'nombre' => session()->get('nombre'),
            'rol'    => session()->get('rol'),
            'email'  => session()->get('email'),
            'instrumentos' => $clientId > 0 ? (new ClientInstrumentAccess())->enabledMap($clientId) : [],
        ]);
    }
}
