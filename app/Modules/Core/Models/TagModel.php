<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class TagModel extends BaseModel
{
    protected $table         = 'tags';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'name', 'color'];
}
