<?php

use App\Modules\Plans\Controllers\TherapeuticPlanController;
use CodeIgniter\Router\RouteCollection;

/**
 * Rotas do modulo de Planos Terapeuticos.
 *
 * @var RouteCollection $routes
 */
$routes->group('plans', ['filter' => 'auth'], static function ($routes) {
    $routes->get('show/(:num)', [TherapeuticPlanController::class, 'show'], ['as' => 'plans.show']);
    $routes->post('store', [TherapeuticPlanController::class, 'store'], ['as' => 'plans.store']);
    $routes->post('toggle-item/(:num)', [TherapeuticPlanController::class, 'toggleItem'], ['as' => 'plans.toggleItem']);
});
