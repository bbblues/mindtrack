<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class SpecialtyModel extends BaseModel
{
    protected $table         = 'specialties';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'name'];
}
