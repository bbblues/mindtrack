<?php

namespace App\Database\Seeds;

use App\Modules\Core\Models\ModuleModel;
use App\Modules\Core\Models\UserModel;
use CodeIgniter\Database\Seeder;
use RuntimeException;

class CoreSeeder extends Seeder
{
    public function run()
    {
        $this->seedModules();
        $this->seedSuperAdmin();
    }

    /**
     * Semeia o catalogo de modulos base do SaaS.
     */
    private function seedModules(): void
    {
        $moduleModel = new ModuleModel();

        $modules = [
            [
                'name'        => 'Core',
                'slug'        => 'core',
                'description' => 'Gestao de clientes e acesso',
                'is_core'     => 1,
                'is_active'   => 1,
            ],
            [
                'name'        => 'Pacientes',
                'slug'        => 'patients',
                'description' => 'Gestao de pacientes',
                'is_core'     => 1,
                'is_active'   => 1,
            ],
            [
                'name'        => 'Agenda',
                'slug'        => 'agenda',
                'description' => 'Agenda de atendimentos',
                'is_core'     => 1,
                'is_active'   => 1,
            ],
        ];

        $existingSlugs = array_column($moduleModel->findAll(), 'slug');

        foreach ($modules as $module) {
            if (in_array($module['slug'], $existingSlugs, true)) {
                continue;
            }

            if ($moduleModel->insert($module) === false) {
                throw new RuntimeException(
                    'Falha ao semear o modulo ' . $module['slug'] . ': ' . implode(' ', $moduleModel->errors())
                );
            }
        }
    }

    /**
     * Cria o Super Admin (dono do SaaS) sem vinculo com tenant.
     */
    private function seedSuperAdmin(): void
    {
        $userModel = new UserModel();

        if ($userModel->where('email', 'admin@mindtrack.com')->first() !== null) {
            return;
        }

        $inserted = $userModel->insert([
            'tenant_id'      => null,
            'name'           => 'Super Admin',
            'email'          => 'admin@mindtrack.com',
            'password'       => 'admin123',
            'is_super_admin' => 1,
            'is_active'      => 1,
        ]);

        if ($inserted === false) {
            throw new RuntimeException(
                'Falha ao criar o Super Admin: ' . implode(' ', $userModel->errors())
            );
        }
    }
}
