<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class RoomModel extends BaseModel
{
    protected $table         = 'rooms';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'name', 'unit_id'];
}
