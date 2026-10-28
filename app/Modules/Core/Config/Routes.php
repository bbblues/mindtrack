<?php

use App\Modules\Core\Controllers\TenantController;
use CodeIgniter\Router\RouteCollection;

/**
 * Rotas do modulo Core (Gestao SaaS do Super Admin).
 *
 * @var RouteCollection $routes
 */
$routes->group('tenants', ['filter' => ['auth', 'superadmin']], static function ($routes) {
    $routes->get('/', [TenantController::class, 'index'], ['as' => 'tenants.index']);
    $routes->get('create', [TenantController::class, 'create'], ['as' => 'tenants.create']);
    $routes->post('/', [TenantController::class, 'store'], ['as' => 'tenants.store']);
    $routes->get('edit/(:num)', [TenantController::class, 'edit'], ['as' => 'tenants.edit']);
    $routes->post('update/(:num)', [TenantController::class, 'update'], ['as' => 'tenants.update']);
});
