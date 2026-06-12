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
$routes->get('tablero', 'ClienteTableroController::mine', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('respuestas', 'ClienteRespuestasController::mine', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('respuestas/exportar', 'ClienteRespuestasController::exportMine', ['filter' => 'role:cliente,consejo,comite']);

$routes->group('admin', ['filter' => 'role:superadmin,admin'], static function ($routes) {
    $routes->get('clientes', 'Admin\ClientesController::index');
    $routes->get('clientes/new', 'Admin\ClientesController::new');
    $routes->post('clientes', 'Admin\ClientesController::create');
    $routes->get('clientes/(:num)/tablero', 'ClienteTableroController::admin/$1');
    $routes->get('clientes/(:num)/respuestas', 'ClienteRespuestasController::admin/$1');
    $routes->get('clientes/(:num)/respuestas/exportar', 'ClienteRespuestasController::exportAdmin/$1');
    $routes->get('clientes/(:num)/config', 'Admin\ClienteConfiguracionController::show/$1');
    $routes->post('clientes/(:num)/config/tipo', 'Admin\ClienteConfiguracionController::updateTipo/$1');
    $routes->post('clientes/(:num)/config/torres', 'Admin\ClienteConfiguracionController::createTorre/$1');
    $routes->post('clientes/(:num)/config/torres/(:num)/delete', 'Admin\ClienteConfiguracionController::deleteTorre/$1/$2');
    $routes->post('clientes/(:num)/config/generar-casas', 'Admin\ClienteConfiguracionController::generateCasas/$1');
    $routes->post('clientes/(:num)/config/generar-apartamentos', 'Admin\ClienteConfiguracionController::generateApartamentos/$1');
    $routes->post('clientes/(:num)/config/inmuebles/(:num)/delete', 'Admin\ClienteConfiguracionController::deleteInmueble/$1/$2');
    $routes->get('clientes/(:num)/usuarios', 'Admin\ClienteUsuariosController::index/$1');
    $routes->get('clientes/(:num)/usuarios/new', 'Admin\ClienteUsuariosController::new/$1');
    $routes->post('clientes/(:num)/usuarios', 'Admin\ClienteUsuariosController::create/$1');
    $routes->get('clientes/(:num)/usuarios/(:num)/edit', 'Admin\ClienteUsuariosController::edit/$1/$2');
    $routes->post('clientes/(:num)/usuarios/(:num)', 'Admin\ClienteUsuariosController::update/$1/$2');
    $routes->post('clientes/(:num)/usuarios/(:num)/delete', 'Admin\ClienteUsuariosController::delete/$1/$2');
    $routes->get('clientes/(:num)', 'Admin\ClientesController::show/$1');
    $routes->get('clientes/(:num)/edit', 'Admin\ClientesController::edit/$1');
    $routes->post('clientes/(:num)', 'Admin\ClientesController::update/$1');
    $routes->post('clientes/(:num)/logo/delete', 'Admin\ClientesController::removeLogo/$1');
    $routes->post('clientes/(:num)/delete', 'Admin\ClientesController::delete/$1');
});
