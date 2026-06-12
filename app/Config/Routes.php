<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Entrada: redirige segun sesion
$routes->get('/', 'AuthController::index');

// Autenticacion (publicas)
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attemptLogin');
$routes->get('logout', 'AuthController::logout');

// Area autenticada
$routes->get('dashboard', 'DashboardController::index', ['filter' => 'auth']);
