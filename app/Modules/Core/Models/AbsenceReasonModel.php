<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class AbsenceReasonModel extends BaseModel
{
    protected $table         = 'absence_reasons';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'name'];
}
