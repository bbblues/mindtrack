<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class ModuleTenantModel extends BaseModel
{
    protected $table         = 'module_tenant';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'module_id', 'is_active'];
}
