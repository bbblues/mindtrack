<?php

namespace App\Modules\Protocols\Models;

use App\Models\BaseModel;

class ProtocolLevelModel extends BaseModel
{
    protected $table         = 'protocol_levels';
    protected $primaryKey    = 'id';
    protected $includeGlobalRows = true;
    protected $allowedFields = [
        'tenant_id',
        'protocol_id',
        'name',
        'section',
        'description',
        'sort_order',
    ];

    protected $validationRules = [
        'protocol_id' => 'required|integer',
        'name'        => 'required|max_length[255]',
        'section'     => 'permit_empty|max_length[100]',
        'description' => 'permit_empty',
        'sort_order'  => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'O nome do nivel e obrigatorio.',
            'max_length' => 'O nome do nivel deve ter no maximo 255 caracteres.',
        ],
        'section' => [
            'max_length' => 'A secao deve ter no maximo 100 caracteres.',
        ],
    ];
}
