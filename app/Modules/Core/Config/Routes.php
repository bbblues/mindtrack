<?php

use App\Modules\Core\Controllers\HealthInsuranceController;
use App\Modules\Core\Controllers\NoteTemplateController;
use App\Modules\Core\Controllers\ReasonController;
use App\Modules\Core\Controllers\RoleController;
use App\Modules\Core\Controllers\RoomController;
use App\Modules\Core\Controllers\SettingsController;
use App\Modules\Core\Controllers\SpecialtyController;
use App\Modules\Core\Controllers\TagController;
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

$routes->group('users', ['filter' => ['auth', 'role:admin,gestor']], static function ($routes) {
    $routes->get('/', [UserController::class, 'index'], ['as' => 'users.index']);
    $routes->get('create', [UserController::class, 'create'], ['as' => 'users.create']);
    $routes->post('/', [UserController::class, 'store'], ['as' => 'users.store']);
    $routes->get('edit/(:num)', [UserController::class, 'edit'], ['as' => 'users.edit']);
    $routes->post('update/(:num)', [UserController::class, 'update'], ['as' => 'users.update']);
});

$routes->group('roles', ['filter' => ['auth', 'role:admin,gestor']], static function ($routes) {
    $routes->get('/', [RoleController::class, 'index'], ['as' => 'roles.index']);
    $routes->post('/', [RoleController::class, 'store'], ['as' => 'roles.store']);
    $routes->post('update/(:num)', [RoleController::class, 'update'], ['as' => 'roles.update']);
    $routes->post('delete/(:num)', [RoleController::class, 'destroy'], ['as' => 'roles.destroy']);
});

$routes->group('settings', ['filter' => ['auth', 'role:admin,gestor']], static function ($routes) {
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

    $routes->get('rooms', [RoomController::class, 'index'], ['as' => 'settings.rooms.index']);
    $routes->post('rooms', [RoomController::class, 'store'], ['as' => 'settings.rooms.store']);
    $routes->post('rooms/update/(:num)', [RoomController::class, 'update'], ['as' => 'settings.rooms.update']);
    $routes->post('rooms/delete/(:num)', [RoomController::class, 'destroy'], ['as' => 'settings.rooms.destroy']);

    $routes->get('tags', [TagController::class, 'index'], ['as' => 'settings.tags.index']);
    $routes->post('tags', [TagController::class, 'store'], ['as' => 'settings.tags.store']);
    $routes->post('tags/update/(:num)', [TagController::class, 'update'], ['as' => 'settings.tags.update']);
    $routes->post('tags/delete/(:num)', [TagController::class, 'destroy'], ['as' => 'settings.tags.destroy']);

    $routes->get('reasons', [ReasonController::class, 'index'], ['as' => 'settings.reasons.index']);
    $routes->post('reasons/absence', [ReasonController::class, 'storeAbsence'], ['as' => 'settings.reasons.absence.store']);
    $routes->post('reasons/absence/update/(:num)', [ReasonController::class, 'updateAbsence'], ['as' => 'settings.reasons.absence.update']);
    $routes->post('reasons/absence/delete/(:num)', [ReasonController::class, 'destroyAbsence'], ['as' => 'settings.reasons.absence.destroy']);
    $routes->post('reasons/discharge', [ReasonController::class, 'storeDischarge'], ['as' => 'settings.reasons.discharge.store']);
    $routes->post('reasons/discharge/update/(:num)', [ReasonController::class, 'updateDischarge'], ['as' => 'settings.reasons.discharge.update']);
    $routes->post('reasons/discharge/delete/(:num)', [ReasonController::class, 'destroyDischarge'], ['as' => 'settings.reasons.discharge.destroy']);

    $routes->get('note-templates', [NoteTemplateController::class, 'index'], ['as' => 'settings.note-templates.index']);
    $routes->post('note-templates', [NoteTemplateController::class, 'store'], ['as' => 'settings.note-templates.store']);
    $routes->post('note-templates/update/(:num)', [NoteTemplateController::class, 'update'], ['as' => 'settings.note-templates.update']);
    $routes->post('note-templates/delete/(:num)', [NoteTemplateController::class, 'destroy'], ['as' => 'settings.note-templates.destroy']);
});

