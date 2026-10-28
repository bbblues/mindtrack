<?php

namespace App\Controllers;

use App\Modules\Core\Models\ModuleModel;
use App\Modules\Core\Models\TenantModel;
use App\Modules\Patients\Models\PatientModel;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $isSuperAdmin = (int) session('is_super_admin') === 1;

        $metrics = [];
        if ($isSuperAdmin) {
            $tenantModel  = new TenantModel();
            $patientModel = new PatientModel();
            $moduleModel  = new ModuleModel();

            $metrics = [
                'total_tenants'  => (int) $tenantModel->countAllResults(),
                'total_patients' => (int) $patientModel->countAllResults(),
                'total_modules'  => (int) $moduleModel->countAllResults(),
            ];
        }

        return view('dashboard', [
            'title'        => 'Dashboard',
            'isSuperAdmin' => $isSuperAdmin,
            'metrics'      => $metrics,
            'user'         => [
                'name'           => (string) (session('user_name') ?? 'Usuario'),
                'is_super_admin' => $isSuperAdmin,
            ],
        ]);
    }
}
