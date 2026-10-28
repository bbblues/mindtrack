<?php

use App\Modules\Protocols\Controllers\ProtocolController;
use CodeIgniter\Router\RouteCollection;

/**
 * Rotas do modulo de Protocolos.
 *
 * @var RouteCollection $routes
 */
$routes->group('protocols', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', [ProtocolController::class, 'index'], ['as' => 'protocols.index']);
    $routes->get('create', [ProtocolController::class, 'create'], ['as' => 'protocols.create']);
    $routes->post('/', [ProtocolController::class, 'store'], ['as' => 'protocols.store']);
});
