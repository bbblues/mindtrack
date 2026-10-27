<?php

use App\Controllers\DashboardController;
use App\Modules\Core\Controllers\AuthController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', static function () {
    return session('user_id') === null
        ? redirect()->to('/login')
        : redirect()->to('/dashboard');
});

$routes->get('/login', [AuthController::class, 'index']);
$routes->post('/login', [AuthController::class, 'authenticate']);
$routes->get('/logout', [AuthController::class, 'logout']);

$routes->get('/dashboard', [DashboardController::class, 'index'], ['filter' => 'auth']);

require_once __DIR__ . '/../Modules/Patients/Config/Routes.php';


