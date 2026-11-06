<?php

use App\Modules\Evolutions\Controllers\EvolutionController;
use CodeIgniter\Router\RouteCollection;

/**
 * Rotas do modulo de Evolucao de Sessao.
 *
 * @var RouteCollection $routes
 */
$routes->group('evolutions', ['filter' => 'auth'], static function ($routes) {
    $routes->get('create/(:num)', [EvolutionController::class, 'create'], ['as' => 'evolutions.create']);
    $routes->post('store', [EvolutionController::class, 'store'], ['as' => 'evolutions.store']);
    $routes->post('update/(:num)', [EvolutionController::class, 'update'], ['as' => 'evolutions.update']);
    $routes->post('finalize/(:num)', [EvolutionController::class, 'finalize'], ['as' => 'evolutions.finalize']);
    $routes->get('(:num)', [EvolutionController::class, 'index'], ['as' => 'evolutions.index']);
});
