<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class UnitModel extends BaseModel
{
    protected $table         = 'units';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'name', 'cnpj', 'is_active'];
}
