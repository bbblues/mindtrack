<?php

namespace App\Modules\Protocols\Models;

use App\Models\BaseModel;

class ProtocolSkillModel extends BaseModel
{
    protected $table         = 'protocol_skills';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'level_id',
        'name',
        'description',
        'sort_order',
    ];

    protected $validationRules = [
        'level_id'    => 'required|integer',
        'name'        => 'required|max_length[255]',
        'description' => 'permit_empty',
        'sort_order'  => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'O nome da habilidade e obrigatorio.',
            'max_length' => 'O nome da habilidade deve ter no maximo 255 caracteres.',
        ],
    ];
}
