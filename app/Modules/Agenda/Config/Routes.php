<?php

use App\Modules\Agenda\Controllers\AppointmentController;
use CodeIgniter\Router\RouteCollection;

/**
 * Rotas do modulo de Agenda.
 *
 * @var RouteCollection $routes
 */
$routes->group('agenda', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', [AppointmentController::class, 'index'], ['as' => 'agenda.index']);
    $routes->post('store', [AppointmentController::class, 'store'], ['as' => 'agenda.store']);
});
