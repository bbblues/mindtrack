<?php

use App\Modules\Core\Controllers\HealthInsuranceController;
use App\Modules\Core\Controllers\RoleController;
use App\Modules\Core\Controllers\SettingsController;
use App\Modules\Core\Controllers\SpecialtyController;
use App\Modules\Core\Controllers\TenantController;
use App\Modules\Core\Controllers\UnitController;
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

$routes->group('roles', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', [RoleController::class, 'index'], ['as' => 'roles.index']);
    $routes->post('/', [RoleController::class, 'store'], ['as' => 'roles.store']);
    $routes->post('update/(:num)', [RoleController::class, 'update'], ['as' => 'roles.update']);
    $routes->post('delete/(:num)', [RoleController::class, 'destroy'], ['as' => 'roles.destroy']);
});

$routes->group('settings', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', [SettingsController::class, 'index'], ['as' => 'settings.index']);

    $routes->get('units', [UnitController::class, 'index'], ['as' => 'settings.units.index']);
    $routes->post('units', [UnitController::class, 'store'], ['as' => 'settings.units.store']);
    $routes->post('units/update/(:num)', [UnitController::class, 'update'], ['as' => 'settings.units.update']);
    $routes->post('units/delete/(:num)', [UnitController::class, 'destroy'], ['as' => 'settings.units.destroy']);

    $routes->get('specialties', [SpecialtyController::class, 'index'], ['as' => 'settings.specialties.index']);
    $routes->post('specialties', [SpecialtyController::class, 'store'], ['as' => 'settings.specialties.store']);
    $routes->post('specialties/update/(:num)', [SpecialtyController::class, 'update'], ['as' => 'settings.specialties.update']);
    $routes->post('specialties/delete/(:num)', [SpecialtyController::class, 'destroy'], ['as' => 'settings.specialties.destroy']);

    $routes->get('insurances', [HealthInsuranceController::class, 'index'], ['as' => 'settings.insurances.index']);
    $routes->post('insurances', [HealthInsuranceController::class, 'store'], ['as' => 'settings.insurances.store']);
    $routes->post('insurances/update/(:num)', [HealthInsuranceController::class, 'update'], ['as' => 'settings.insurances.update']);
    $routes->post('insurances/delete/(:num)', [HealthInsuranceController::class, 'destroy'], ['as' => 'settings.insurances.destroy']);
});

