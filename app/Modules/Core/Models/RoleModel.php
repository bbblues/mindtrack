<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class RoleModel extends BaseModel
{
    protected $table         = 'roles';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'name', 'slug', 'description', 'is_active'];

    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'slug' => 'required|alpha_dash|max_length[100]',
    ];
}
