<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class PermissionModel extends BaseModel
{
    protected $table         = 'permissions';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name', 'slug', 'module', 'description'];

    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'slug' => 'required|alpha_dash|max_length[100]',
    ];
}
