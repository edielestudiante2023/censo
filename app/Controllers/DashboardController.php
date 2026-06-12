<?php

namespace App\Controllers;

class DashboardController extends BaseController
{
    public function index()
    {
        return view('dashboard/index', [
            'nombre' => session()->get('nombre'),
            'rol'    => session()->get('rol'),
            'email'  => session()->get('email'),
        ]);
    }
}
