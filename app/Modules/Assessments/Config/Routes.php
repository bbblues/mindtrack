<?php

use App\Modules\Assessments\Controllers\AssessmentController;
use CodeIgniter\Router\RouteCollection;

/**
 * Rotas do modulo de Avaliacoes de Pacientes.
 *
 * @var RouteCollection $routes
 */
$routes->group('assessments', ['filter' => 'auth'], static function ($routes) {
    $routes->post('create', [AssessmentController::class, 'create'], ['as' => 'assessments.create']);
    $routes->get('show/(:num)', [AssessmentController::class, 'show'], ['as' => 'assessments.show']);
    $routes->post('update-item', [AssessmentController::class, 'updateItem'], ['as' => 'assessments.updateItem']);
});
