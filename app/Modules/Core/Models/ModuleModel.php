<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class ModuleModel extends BaseModel
{
    protected $table         = 'modules';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name', 'slug', 'description', 'is_core', 'is_active'];

    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'slug' => 'required|alpha_dash|max_length[100]',
    ];
}
