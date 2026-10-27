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
    $routes->get('events', [AppointmentController::class, 'events'], ['as' => 'agenda.events']);
    $routes->post('store', [AppointmentController::class, 'store'], ['as' => 'agenda.store']);
    $routes->post('update-schedule', [AppointmentController::class, 'updateSchedule'], ['as' => 'agenda.updateSchedule']);
});
