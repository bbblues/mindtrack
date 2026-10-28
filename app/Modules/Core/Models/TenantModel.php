<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class TenantModel extends BaseModel
{
    protected $table         = 'tenants';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name', 'slug', 'cnpj', 'email', 'phone', 'is_active'];

    protected $validationRules = [
        'name'  => 'required|max_length[255]',
        'slug'  => 'permit_empty|alpha_dash|max_length[255]|is_unique[tenants.slug,id,{id}]',
        'email' => 'permit_empty|valid_email|max_length[255]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'O nome da clínica é obrigatório.',
        ],
        'slug' => [
            'is_unique' => 'Este domínio (slug) já está em uso.',
        ],
        'email' => [
            'valid_email' => 'Informe um e-mail válido.',
        ],
    ];

    /**
     * Cria um tenant e vincula automaticamente os modulos base
     * (core, patients e agenda) mais os modulos extras selecionados.
     *
     * @param list<mixed> $selectedModuleIds
     *
     * @return int|false ID do tenant criado ou false em caso de falha.
     */
    public function createTenantWithBaseModules(array $data, array $selectedModuleIds = [])
    {
        $tenantId = $this->insert($data);

        if ($tenantId === false) {
            return false;
        }

        $this->syncModules((int) $tenantId, $selectedModuleIds);

        // Cria os 4 cargos padrao (RBAC) vinculados ao novo tenant.
        (new RoleModel())->createDefaultRolesForTenant((int) $tenantId);

        return (int) $tenantId;
    }

    /**
     * Sincroniza os modulos ativos de um tenant (feature flags).
     * Sempre mantem os modulos base (core, patients, agenda).
     *
     * @param list<mixed> $selectedModuleIds
     */
    public function syncModules(int $tenantId, array $selectedModuleIds = []): void
    {
        $moduleIds = $this->resolveModuleIds($selectedModuleIds);

        // Remove (hard delete) os vinculos atuais do pivot de feature flags.
        $this->db->table('module_tenant')
            ->where('tenant_id', $tenantId)
            ->delete();

        $moduleTenantModel = new ModuleTenantModel();

        foreach ($moduleIds as $moduleId) {
            $moduleTenantModel->insert([
                'tenant_id' => $tenantId,
                'module_id' => $moduleId,
                'is_active' => 1,
            ]);
        }
    }

    /**
     * Junta os ids base (core, patients, agenda) com os selecionados.
     *
     * @param list<mixed> $selectedModuleIds
     *
     * @return list<int>
     */
    private function resolveModuleIds(array $selectedModuleIds): array
    {
        $moduleModel = new ModuleModel();

        $baseIds = array_map('intval', array_column(
            $moduleModel->whereIn('slug', ['core', 'patients', 'agenda'])->findAll(),
            'id'
        ));

        $selected = array_map('intval', $selectedModuleIds);

        return array_values(array_unique(array_merge($baseIds, $selected)));
    }
}
