<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class HealthInsuranceModel extends BaseModel
{
    protected $table         = 'health_insurances';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'name', 'ans_registry', 'requires_card', 'card_format'];
}
