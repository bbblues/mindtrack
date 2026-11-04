<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class PermissionModel extends BaseModel
{
    protected $table         = 'permissions';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name', 'slug', 'module', 'description', 'scope', 'sort_order'];

    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'slug' => 'required|max_length[100]|regex_match[/^[a-z0-9_]+(\.[a-z0-9_]+)*$/]',
    ];
}
