<?php

use App\Modules\Agenda\Controllers\ClinicScheduleController;
use App\Modules\Agenda\Controllers\HolidayController;
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

$routes->group('roles', ['filter' => ['auth', 'permission:settings.manage_roles']], static function ($routes) {
    $routes->get('permissions/(:num)', [RoleController::class, 'permissions'], ['as' => 'roles.permissions']);
    $routes->post('permissions/(:num)', [RoleController::class, 'updatePermissions'], ['as' => 'roles.updatePermissions']);
});

$routes->group('settings', ['filter' => ['auth', 'role:admin,gestor']], static function ($routes) {
    $routes->get('/', [SettingsController::class, 'index'], ['as' => 'settings.index']);

    // Configuracoes da Agenda (duracao padrao da sessao).
    $routes->get('appointment', [SettingsController::class, 'appointment'], ['as' => 'settings.appointment.index']);
    $routes->post('appointment', [SettingsController::class, 'storeAppointment'], ['as' => 'settings.appointment.store']);

    // Horario de funcionamento da clinica.
    $routes->get('clinic-schedule', [ClinicScheduleController::class, 'index'], ['as' => 'settings.clinicSchedule.index']);
    $routes->post('clinic-schedule', [ClinicScheduleController::class, 'store'], ['as' => 'settings.clinicSchedule.store']);

    // Feriados nacionais (sync) + feriados locais/datas de fechamento + cidade.
    $routes->get('holidays', [HolidayController::class, 'index'], ['as' => 'settings.holidays.index']);
    $routes->post('holidays/sync-national', [HolidayController::class, 'syncNational'], ['as' => 'settings.holidays.syncNational']);
    $routes->post('holidays/city', [HolidayController::class, 'storeCity'], ['as' => 'settings.holidays.city.store']);
    $routes->post('holidays/city/update/(:num)', [HolidayController::class, 'updateCity'], ['as' => 'settings.holidays.city.update']);
    $routes->post('holidays/city/delete/(:num)', [HolidayController::class, 'deleteCity'], ['as' => 'settings.holidays.city.destroy']);
    $routes->post('holidays', [HolidayController::class, 'store'], ['as' => 'settings.holidays.store']);
    $routes->post('holidays/update/(:num)', [HolidayController::class, 'update'], ['as' => 'settings.holidays.update']);
    $routes->post('holidays/delete/(:num)', [HolidayController::class, 'destroy'], ['as' => 'settings.holidays.destroy']);

    // Equipe e acessos (mesmos controllers de /users e /roles, dentro do hub de configuracoes).
    $routes->get('users', [UserController::class, 'index'], ['as' => 'settings.users.index']);
    $routes->get('users/create', [UserController::class, 'create'], ['as' => 'settings.users.create']);
    $routes->post('users', [UserController::class, 'store'], ['as' => 'settings.users.store']);
    $routes->get('users/edit/(:num)', [UserController::class, 'edit'], ['as' => 'settings.users.edit']);
    $routes->post('users/update/(:num)', [UserController::class, 'update'], ['as' => 'settings.users.update']);

    $routes->get('roles', [RoleController::class, 'index'], ['as' => 'settings.roles.index']);
    $routes->post('roles', [RoleController::class, 'store'], ['as' => 'settings.roles.store']);
    $routes->post('roles/update/(:num)', [RoleController::class, 'update'], ['as' => 'settings.roles.update']);
    $routes->post('roles/delete/(:num)', [RoleController::class, 'destroy'], ['as' => 'settings.roles.destroy']);
    $routes->get('roles/permissions/(:num)', [RoleController::class, 'permissions'], ['as' => 'settings.roles.permissions', 'filter' => 'permission:settings.manage_roles']);
    $routes->post('roles/permissions/(:num)', [RoleController::class, 'updatePermissions'], ['as' => 'settings.roles.updatePermissions', 'filter' => 'permission:settings.manage_roles']);

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

