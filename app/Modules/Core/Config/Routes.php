<?php

use App\Modules\Core\Controllers\TenantController;
use App\Modules\Core\Controllers\UserController;
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

$routes->group('users', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', [UserController::class, 'index'], ['as' => 'users.index']);
    $routes->get('create', [UserController::class, 'create'], ['as' => 'users.create']);
    $routes->post('/', [UserController::class, 'store'], ['as' => 'users.store']);
    $routes->get('edit/(:num)', [UserController::class, 'edit'], ['as' => 'users.edit']);
    $routes->post('update/(:num)', [UserController::class, 'update'], ['as' => 'users.update']);
});
