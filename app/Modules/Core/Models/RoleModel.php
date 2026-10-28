<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class RoleModel extends BaseModel
{
    /**
     * Slugs dos cargos padrao criados automaticamente em todo tenant.
     *
     * @var list<string>
     */
    public const DEFAULT_ROLE_SLUGS = ['admin', 'gestor', 'profissional', 'recepcao'];

    protected $table         = 'roles';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'name', 'slug', 'description', 'is_active'];

    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'slug' => 'required|alpha_dash|max_length[100]',
    ];

    /**
     * Cria os 4 cargos padrao de uma nova clinica (tenant).
     */
    public function createDefaultRolesForTenant(int $tenantId): void
    {
        $defaults = [
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Acesso administrativo completo da clínica.'],
            ['name' => 'Gestor', 'slug' => 'gestor', 'description' => 'Gerencia equipe, usuários e relatórios.'],
            ['name' => 'Profissional', 'slug' => 'profissional', 'description' => 'Profissional clínico que atende pacientes.'],
            ['name' => 'Recepção', 'slug' => 'recepcao', 'description' => 'Responsável pela recepção e agenda.'],
        ];

        foreach ($defaults as $role) {
            $exists = $this->db->table('roles')
                ->where('tenant_id', $tenantId)
                ->where('slug', $role['slug'])
                ->get()
                ->getRowArray();

            if ($exists !== null) {
                continue;
            }

            if ($this->insert([
                'tenant_id'   => $tenantId,
                'name'        => $role['name'],
                'slug'        => $role['slug'],
                'description' => $role['description'],
                'is_active'   => 1,
            ]) === false) {
                log_message('error', 'Falha ao criar o cargo padrão "' . $role['slug'] . '" para o tenant ' . $tenantId . '.');
            }
        }
    }

    /**
     * Verifica se o slug pertence a um cargo padrao do sistema.
     */
    public function isDefaultSlug(string $slug): bool
    {
        return in_array($slug, self::DEFAULT_ROLE_SLUGS, true);
    }

    /**
     * Retorna os cargos visiveis no formulario de usuario.
     *
     * Quando ha um tenant, retorna APENAS os cargos daquele tenant
     * (cada clinica nova ja recebe seus 4 cargos padrao automaticamente).
     * Quando nao ha tenant (contexto do Super Admin), retorna os cargos
     * globais (tenant_id IS NULL).
     *
     * @return array<int, array<string, mixed>>
     */
    public function rolesForTenant(?int $tenantId): array
    {
        $builder = $this->db->table('roles');

        $builder->where('deleted_at', null);

        if ($tenantId === null) {
            $builder->where('tenant_id', null);
        } else {
            $builder->where('tenant_id', $tenantId);
        }

        return $builder->orderBy('name', 'ASC')->get()->getResultArray();
    }
}

