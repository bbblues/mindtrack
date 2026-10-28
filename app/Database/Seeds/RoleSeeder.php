<?php

namespace App\Database\Seeds;

use App\Modules\Core\Models\RoleModel;
use App\Modules\Core\Models\TenantModel;
use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Semeia os cargos (roles) base do RBAC.
 *
 * Os cargos sao vinculados ao tenant padrao (o primeiro cadastrado) quando
 * existir; caso contrario, sao criados como cargos globais (tenant_id = null).
 */
class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name'        => 'Admin',
                'slug'        => 'admin',
                'description' => 'Acesso administrativo completo da clinica.',
            ],
            [
                'name'        => 'Gestor',
                'slug'        => 'gestor',
                'description' => 'Gerencia equipe, usuarios e relatorios.',
            ],
            [
                'name'        => 'Profissional',
                'slug'        => 'profissional',
                'description' => 'Profissional clinico que atende pacientes.',
            ],
            [
                'name'        => 'Recepção',
                'slug'        => 'recepcao',
                'description' => 'Responsavel pela recepcao e agenda.',
            ],
        ];

        $tenantId  = $this->defaultTenantId();
        $roleModel = new RoleModel();

        foreach ($roles as $role) {
            $query = $this->db->table('roles')->where('slug', $role['slug']);
            if ($tenantId !== null) {
                $query->where('tenant_id', $tenantId);
            } else {
                $query->where('tenant_id', null);
            }

            if ($query->get()->getRowArray() !== null) {
                continue;
            }

            if ($roleModel->insert([
                'tenant_id'   => $tenantId,
                'name'        => $role['name'],
                'slug'        => $role['slug'],
                'description' => $role['description'],
                'is_active'   => 1,
            ]) === false) {
                throw new RuntimeException(
                    'Falha ao semear o cargo ' . $role['slug'] . ': ' . implode(' ', $roleModel->errors())
                );
            }
        }
    }

    /**
     * Retorna o id do tenant padrao (o primeiro cadastrado) ou null.
     */
    private function defaultTenantId(): ?int
    {
        $tenant = (new TenantModel())->orderBy('id', 'ASC')->first();

        return $tenant !== null ? (int) $tenant['id'] : null;
    }
}
