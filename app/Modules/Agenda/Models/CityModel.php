<?php

namespace App\Modules\Agenda\Models;

use App\Models\BaseModel;

class CityModel extends BaseModel
{
    protected $table         = 'cities';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'city_code', 'city_name', 'state', 'is_active'];

    protected $validationRules = [
        'city_name' => 'required|max_length[255]',
        'state'     => 'required|exact_length[2]',
        'city_code' => 'permit_empty|max_length[7]',
        'is_active' => 'permit_empty|in_list[0,1]',
    ];

    /**
     * Cidades cadastradas pelo tenant.
     *
     * @return list<array<string, mixed>>
     */
    public function forTenant(?int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)
            ->orderBy('city_name', 'ASC')
            ->findAll();
    }

    public function forTenantById(?int $tenantId, int $id): ?array
    {
        $row = $this->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $row !== null ? $row : null;
    }
}
