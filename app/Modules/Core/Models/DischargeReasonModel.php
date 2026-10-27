<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class DischargeReasonModel extends BaseModel
{
    protected $table         = 'discharge_reasons';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'name'];
}
