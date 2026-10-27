<?php

use App\Modules\Patients\Controllers\PatientController;
use CodeIgniter\Router\RouteCollection;

/**
 * Rotas do modulo de Pacientes.
 *
 * @var RouteCollection $routes
 */
$routes->group('patients', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', [PatientController::class, 'index'], ['as' => 'patients.index']);
    $routes->get('create', [PatientController::class, 'create'], ['as' => 'patients.create']);
    $routes->post('/', [PatientController::class, 'store'], ['as' => 'patients.store']);
    $routes->get('edit/(:num)', [PatientController::class, 'edit'], ['as' => 'patients.edit']);
    $routes->post('update/(:num)', [PatientController::class, 'update'], ['as' => 'patients.update']);
    $routes->post('toggle-status/(:num)', [PatientController::class, 'toggleStatus'], ['as' => 'patients.toggleStatus']);
});
