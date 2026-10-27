<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class NoteTemplateModel extends BaseModel
{
    protected $table         = 'note_templates';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'name', 'content'];
}
