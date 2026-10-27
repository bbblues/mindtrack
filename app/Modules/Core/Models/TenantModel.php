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

    /**
     * Cria um tenant e vincula automaticamente os modulos base
     * (core, patients e agenda) em module_tenant.
     *
     * @return int|false ID do tenant criado ou false em caso de falha.
     */
    public function createTenantWithBaseModules(array $data)
    {
        $tenantId = $this->insert($data);

        if ($tenantId === false) {
            return false;
        }

        $moduleModel       = new ModuleModel();
        $moduleTenantModel = new ModuleTenantModel();

        $baseModules = $moduleModel->whereIn('slug', ['core', 'patients', 'agenda'])->findAll();

        foreach ($baseModules as $module) {
            $moduleTenantModel->insert([
                'tenant_id' => $tenantId,
                'module_id' => $module['id'],
                'is_active' => 1,
            ]);
        }

        return $tenantId;
    }
}
