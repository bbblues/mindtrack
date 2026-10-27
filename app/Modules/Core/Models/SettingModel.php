<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class SettingModel extends BaseModel
{
    protected $table         = 'settings';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'setting_key', 'setting_value'];
}
