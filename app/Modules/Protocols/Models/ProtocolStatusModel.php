<?php

namespace App\Modules\Protocols\Models;

use App\Models\BaseModel;

class ProtocolStatusModel extends BaseModel
{
    protected $table         = 'protocol_statuses';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'protocol_id',
        'name',
        'value',
        'sort_order',
    ];

    protected $validationRules = [
        'protocol_id' => 'required|integer',
        'name'        => 'required|max_length[255]',
        'value'       => 'required|integer',
        'sort_order'  => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'O nome do status e obrigatorio.',
            'max_length' => 'O nome do status deve ter no maximo 255 caracteres.',
        ],
        'value' => [
            'required' => 'O valor do status e obrigatorio.',
            'integer'  => 'O valor do status deve ser um numero inteiro.',
        ],
    ];
}
