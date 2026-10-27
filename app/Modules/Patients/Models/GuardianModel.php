<?php

namespace App\Modules\Patients\Models;

use App\Models\BaseModel;

class GuardianModel extends BaseModel
{
    protected $table         = 'guardians';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'name',
        'relationship',
        'cpf',
        'phone',
        'email',
    ];

    protected $validationRules = [
        'name'         => 'required|max_length[255]',
        'relationship' => 'permit_empty|max_length[100]',
        'cpf'          => 'permit_empty|exact_length[11]',
        'phone'        => 'permit_empty|max_length[20]',
        'email'        => 'permit_empty|valid_email|max_length[255]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'O nome do responsavel e obrigatorio.',
        ],
        'email' => [
            'valid_email' => 'Informe um e-mail valido para o responsavel.',
        ],
    ];
}
