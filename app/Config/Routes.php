<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Entrada: redirige segun sesion
$routes->get('/', 'AuthController::index');

// Autenticacion (publicas)
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attemptLogin');
$routes->get('login/verificar', 'AuthController::mfa');
$routes->post('login/verificar', 'AuthController::verifyMfa');
$routes->get('logout', 'AuthController::logout');
$routes->get('forgot', 'PasswordController::forgot');
$routes->post('forgot', 'PasswordController::sendLink');
$routes->get('reset/(:segment)', 'PasswordController::reset/$1');
$routes->post('reset/(:segment)', 'PasswordController::update/$1');
$routes->get('q/(:segment)', 'QrPublicController::resolve/$1');
$routes->post('q/(:segment)/form', 'QrPublicController::form/$1');
$routes->post('q/(:segment)/submit', 'QrPublicController::submit/$1');
$routes->get('q/(:segment)/pdf', 'QrPublicController::pdf/$1');
$routes->get('privacidad/(:segment)', 'PrivacyPublicController::portal/$1');
  $routes->post('privacidad/(:segment)/consentimiento', 'PrivacyPublicController::consent/$1');
  $routes->post('privacidad/(:segment)/consentimiento/codigo', 'PrivacyPublicController::sendConsentCode/$1');
  $routes->post('privacidad/(:segment)/consentimiento/verificar', 'PrivacyPublicController::verifyConsentCode/$1');
$routes->post('privacidad/(:segment)/solicitud', 'PrivacyPublicController::request/$1');
$routes->get('privacidad/(:segment)/documentos/(:num)', 'PrivacyPublicController::publicDocument/$1/$2');
$routes->post('webhooks/sendgrid/(:segment)', 'PrivacyPublicController::sendgridWebhook/$1');
$routes->get('confidencialidad/(:segment)', 'PrivacyConfidentialityController::show/$1');
$routes->post('confidencialidad/(:segment)/visualizacion', 'PrivacyConfidentialityController::confirmView/$1');
$routes->post('confidencialidad/(:segment)/codigo', 'PrivacyConfidentialityController::sendCode/$1');
$routes->post('confidencialidad/(:segment)/aceptar', 'PrivacyConfidentialityController::accept/$1');
$routes->get('acuerdo-encargado/(:segment)', 'PrivacyProcessorAgreementController::show/$1');
$routes->post('acuerdo-encargado/(:segment)/visualizacion', 'PrivacyProcessorAgreementController::confirmView/$1');
$routes->post('acuerdo-encargado/(:segment)/codigo', 'PrivacyProcessorAgreementController::sendCode/$1');
$routes->post('acuerdo-encargado/(:segment)/aceptar', 'PrivacyProcessorAgreementController::accept/$1');
$routes->post('acuerdo-encargado/(:segment)/incidente', 'PrivacyProcessorAgreementController::reportIncident/$1');
$routes->post('acuerdo-encargado/(:segment)/solicitud', 'PrivacyProcessorAgreementController::forwardRightsRequest/$1');

// Area autenticada
$routes->get('dashboard', 'DashboardController::index', ['filter' => 'auth']);
$routes->get('tablero', 'ClienteTableroController::mine', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('respuestas', 'ClienteRespuestasController::mine', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('respuestas/exportar', 'ClienteRespuestasController::exportMine', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('respuestas/excel', 'ClienteRespuestasController::excelMine', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('respuestas/completo', 'ClienteRespuestasController::completoMine', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('respuestas/pdf/(:segment)/(:num)', 'ClienteRespuestasController::pdfMine/$1/$2', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('inteligencia', 'InteligenciaController::mine', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('inteligencia/exportar', 'InteligenciaController::exportMine', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('inteligencia/excel', 'InteligenciaController::excelMine', ['filter' => 'role:cliente,consejo,comite']);

// Programa de tratamiento de datos del propio conjunto
$routes->get('datos-personales', 'PrivacyController::mine', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('datos-personales/portal/qr.svg', 'PrivacyController::privacyPortalQr', ['filter' => 'role:cliente,consejo,comite']);
$routes->post('datos-personales/programa', 'PrivacyController::saveProgram', ['filter' => 'role:cliente']);
$routes->post('datos-personales/bases/guardar', 'PrivacyController::saveBase', ['filter' => 'role:cliente']);
$routes->post('datos-personales/bases/archivar', 'PrivacyController::deactivateBase', ['filter' => 'role:cliente']);
  $routes->post('datos-personales/finalidades', 'PrivacyController::savePurpose', ['filter' => 'role:cliente']);
  $routes->post('datos-personales/finalidades/sensibles', 'PrivacyController::saveSensitiveDatum', ['filter' => 'role:cliente']);
$routes->post('datos-personales/terceros', 'PrivacyController::saveThirdParty', ['filter' => 'role:cliente']);
$routes->post('datos-personales/terceros/subencargados', 'PrivacyController::saveSubprocessor', ['filter' => 'role:cliente']);
$routes->post('datos-personales/terceros/acuerdos', 'PrivacyController::createProcessorAgreement', ['filter' => 'role:cliente']);
$routes->post('datos-personales/terceros/acuerdos/terminar', 'PrivacyController::terminateProcessorAgreement', ['filter' => 'role:cliente']);
$routes->post('datos-personales/terceros/acuerdos/certificar', 'PrivacyController::certifyProcessorTermination', ['filter' => 'role:cliente']);
$routes->post('datos-personales/terceros/instrucciones', 'PrivacyController::createProcessorInstruction', ['filter' => 'role:cliente']);
$routes->post('datos-personales/documentos/generar', 'PrivacyController::generateDocuments', ['filter' => 'role:cliente']);
$routes->post('datos-personales/documentos/guardar', 'PrivacyController::saveDocument', ['filter' => 'role:cliente']);
  $routes->post('datos-personales/documentos/estado', 'PrivacyController::transitionDocument', ['filter' => 'role:cliente']);
  $routes->post('datos-personales/avisos/publicaciones', 'PrivacyController::publishNoticeVariant', ['filter' => 'role:cliente']);
  $routes->get('datos-personales/documentos/pdf', 'PrivacyController::documentPdf', ['filter' => 'role:cliente,consejo,comite']);
  $routes->get('datos-personales/consentimientos/pdf', 'PrivacyController::consentPdf', ['filter' => 'role:cliente,consejo,comite']);
$routes->post('datos-personales/solicitudes', 'PrivacyController::createRequest', ['filter' => 'role:cliente']);
$routes->post('datos-personales/solicitudes/actualizar', 'PrivacyController::updateRequest', ['filter' => 'role:cliente']);
$routes->post('datos-personales/solicitudes/base', 'PrivacyController::executeRequestBase', ['filter' => 'role:cliente']);
$routes->post('datos-personales/solicitudes/tercero', 'PrivacyController::executeRequestThirdParty', ['filter' => 'role:cliente']);
$routes->post('datos-personales/seguridad/asignaciones', 'PrivacyController::saveSecurityAssignment', ['filter' => 'role:cliente']);
$routes->post('datos-personales/seguridad/controles', 'PrivacyController::recordSecurityControl', ['filter' => 'role:cliente']);
$routes->post('datos-personales/seguridad/usuarios', 'PrivacyController::recordUserPrivacyCompliance', ['filter' => 'role:cliente']);
$routes->post('datos-personales/confidencialidad/generar', 'PrivacyController::createConfidentialityAgreement', ['filter' => 'role:cliente']);
$routes->post('datos-personales/confidencialidad/cerrar', 'PrivacyController::closeConfidentialityAgreement', ['filter' => 'role:cliente']);
$routes->post('datos-personales/seguridad/incidentes', 'PrivacyController::createSecurityIncident', ['filter' => 'role:cliente']);
$routes->post('datos-personales/seguridad/incidentes/actualizar', 'PrivacyController::updateSecurityIncident', ['filter' => 'role:cliente']);
$routes->get('datos-personales/solicitudes/pdf', 'PrivacyController::requestPdf', ['filter' => 'role:cliente,consejo,comite']);
$routes->post('datos-personales/ia/revisar', 'PrivacyController::reviewAi', ['filter' => 'role:cliente']);
$routes->get('datos-personales/exportar', 'PrivacyController::export', ['filter' => 'role:cliente,consejo,comite']);

// QR del propio conjunto (cliente/consejo/comite)
$routes->get('qr', 'ClienteQrController::mine', ['filter' => 'role:cliente,consejo,comite']);
$routes->post('qr', 'ClienteQrController::create', ['filter' => 'role:cliente,consejo,comite']);
$routes->post('qr/(:num)/regenerate', 'ClienteQrController::regenerate/$1', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('qr/(:num).svg', 'ClienteQrController::svg/$1', ['filter' => 'role:cliente,consejo,comite']);
$routes->get('qr/(:num)/pieza', 'ClienteQrController::pieza/$1', ['filter' => 'role:cliente,consejo,comite']);
$routes->post('qr/(:num)', 'ClienteQrController::update/$1', ['filter' => 'role:cliente,consejo,comite']);

$routes->group('admin', ['filter' => 'role:superadmin,admin'], static function ($routes) {
    $routes->get('clientes', 'Admin\ClientesController::index');
    $routes->get('clientes/new', 'Admin\ClientesController::new');
    $routes->post('clientes', 'Admin\ClientesController::create');
    $routes->get('clientes/(:num)/tablero', 'ClienteTableroController::admin/$1');
    $routes->get('clientes/(:num)/respuestas', 'ClienteRespuestasController::admin/$1');
    $routes->get('clientes/(:num)/respuestas/exportar', 'ClienteRespuestasController::exportAdmin/$1');
    $routes->get('clientes/(:num)/respuestas/excel', 'ClienteRespuestasController::excelAdmin/$1');
    $routes->get('clientes/(:num)/respuestas/completo', 'ClienteRespuestasController::completoAdmin/$1');
    $routes->get('clientes/(:num)/respuestas/pdf/(:segment)/(:num)', 'ClienteRespuestasController::pdfAdmin/$1/$2/$3');
    $routes->get('clientes/(:num)/inteligencia', 'InteligenciaController::admin/$1');
    $routes->get('clientes/(:num)/inteligencia/exportar', 'InteligenciaController::exportAdmin/$1');
    $routes->get('clientes/(:num)/inteligencia/excel', 'InteligenciaController::excelAdmin/$1');
    $routes->get('clientes/(:num)/datos-personales', 'PrivacyController::admin/$1');
    $routes->get('clientes/(:num)/datos-personales/portal/qr.svg', 'PrivacyController::privacyPortalQr/$1');
    $routes->post('clientes/(:num)/datos-personales/programa', 'PrivacyController::saveProgram/$1');
    $routes->post('clientes/(:num)/datos-personales/bases/guardar', 'PrivacyController::saveBase/$1');
    $routes->post('clientes/(:num)/datos-personales/bases/archivar', 'PrivacyController::deactivateBase/$1');
      $routes->post('clientes/(:num)/datos-personales/finalidades', 'PrivacyController::savePurpose/$1');
      $routes->post('clientes/(:num)/datos-personales/finalidades/sensibles', 'PrivacyController::saveSensitiveDatum/$1');
    $routes->post('clientes/(:num)/datos-personales/terceros', 'PrivacyController::saveThirdParty/$1');
    $routes->post('clientes/(:num)/datos-personales/terceros/subencargados', 'PrivacyController::saveSubprocessor/$1');
    $routes->post('clientes/(:num)/datos-personales/terceros/acuerdos', 'PrivacyController::createProcessorAgreement/$1');
    $routes->post('clientes/(:num)/datos-personales/terceros/acuerdos/terminar', 'PrivacyController::terminateProcessorAgreement/$1');
    $routes->post('clientes/(:num)/datos-personales/terceros/acuerdos/certificar', 'PrivacyController::certifyProcessorTermination/$1');
    $routes->post('clientes/(:num)/datos-personales/terceros/instrucciones', 'PrivacyController::createProcessorInstruction/$1');
    $routes->post('clientes/(:num)/datos-personales/documentos/generar', 'PrivacyController::generateDocuments/$1');
    $routes->post('clientes/(:num)/datos-personales/documentos/guardar', 'PrivacyController::saveDocument/$1');
      $routes->post('clientes/(:num)/datos-personales/documentos/estado', 'PrivacyController::transitionDocument/$1');
      $routes->post('clientes/(:num)/datos-personales/avisos/publicaciones', 'PrivacyController::publishNoticeVariant/$1');
      $routes->get('clientes/(:num)/datos-personales/documentos/pdf', 'PrivacyController::documentPdf/$1');
      $routes->get('clientes/(:num)/datos-personales/consentimientos/pdf', 'PrivacyController::consentPdf/$1');
    $routes->post('clientes/(:num)/datos-personales/solicitudes', 'PrivacyController::createRequest/$1');
    $routes->post('clientes/(:num)/datos-personales/solicitudes/actualizar', 'PrivacyController::updateRequest/$1');
    $routes->post('clientes/(:num)/datos-personales/solicitudes/base', 'PrivacyController::executeRequestBase/$1');
    $routes->post('clientes/(:num)/datos-personales/solicitudes/tercero', 'PrivacyController::executeRequestThirdParty/$1');
    $routes->post('clientes/(:num)/datos-personales/seguridad/asignaciones', 'PrivacyController::saveSecurityAssignment/$1');
    $routes->post('clientes/(:num)/datos-personales/seguridad/controles', 'PrivacyController::recordSecurityControl/$1');
    $routes->post('clientes/(:num)/datos-personales/seguridad/usuarios', 'PrivacyController::recordUserPrivacyCompliance/$1');
    $routes->post('clientes/(:num)/datos-personales/confidencialidad/generar', 'PrivacyController::createConfidentialityAgreement/$1');
    $routes->post('clientes/(:num)/datos-personales/confidencialidad/cerrar', 'PrivacyController::closeConfidentialityAgreement/$1');
    $routes->post('clientes/(:num)/datos-personales/seguridad/incidentes', 'PrivacyController::createSecurityIncident/$1');
    $routes->post('clientes/(:num)/datos-personales/seguridad/incidentes/actualizar', 'PrivacyController::updateSecurityIncident/$1');
    $routes->get('clientes/(:num)/datos-personales/solicitudes/pdf', 'PrivacyController::requestPdf/$1');
    $routes->post('clientes/(:num)/datos-personales/ia/revisar', 'PrivacyController::reviewAi/$1');
    $routes->get('clientes/(:num)/datos-personales/exportar', 'PrivacyController::export/$1');
    $routes->get('clientes/(:num)/qr', 'Admin\ClienteQrController::index/$1');
    $routes->post('clientes/(:num)/qr', 'Admin\ClienteQrController::create/$1');
    $routes->post('clientes/(:num)/qr/(:num)', 'Admin\ClienteQrController::update/$1/$2');
    $routes->post('clientes/(:num)/qr/(:num)/regenerate', 'Admin\ClienteQrController::regenerate/$1/$2');
    $routes->get('clientes/(:num)/qr/(:num).svg', 'Admin\ClienteQrController::svg/$1/$2');
    $routes->get('clientes/(:num)/qr/(:num)/pieza', 'Admin\ClienteQrController::pieza/$1/$2');
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
