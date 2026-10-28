<?php

namespace App\Modules\Protocols\Models;

use App\Models\BaseModel;

class ProtocolItemModel extends BaseModel
{
    protected $table         = 'protocol_items';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'skill_id',
        'name',
        'description',
        'sort_order',
    ];

    protected $validationRules = [
        'skill_id'    => 'required|integer',
        'name'        => 'required|max_length[255]',
        'description' => 'permit_empty',
        'sort_order'  => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'O nome do item e obrigatorio.',
            'max_length' => 'O nome do item deve ter no maximo 255 caracteres.',
        ],
    ];
}
