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

$routes->group('admin', ['filter' => 'role:superadmin,admin'], static function ($routes) {
    $routes->get('clientes', 'Admin\ClientesController::index');
    $routes->get('clientes/new', 'Admin\ClientesController::new');
    $routes->post('clientes', 'Admin\ClientesController::create');
    $routes->get('clientes/(:num)', 'Admin\ClientesController::show/$1');
    $routes->get('clientes/(:num)/edit', 'Admin\ClientesController::edit/$1');
    $routes->post('clientes/(:num)', 'Admin\ClientesController::update/$1');
    $routes->post('clientes/(:num)/logo/delete', 'Admin\ClientesController::removeLogo/$1');
    $routes->post('clientes/(:num)/delete', 'Admin\ClientesController::delete/$1');
});
