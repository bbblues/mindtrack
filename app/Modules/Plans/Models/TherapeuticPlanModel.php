<?php

namespace App\Modules\Plans\Models;

use App\Models\BaseModel;

class TherapeuticPlanModel extends BaseModel
{
    protected $table         = 'therapeutic_plans';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'patient_id',
        'status',
        'start_date',
        'end_date',
    ];

    protected $validationRules = [
        'patient_id' => 'required|integer',
        'status'     => 'permit_empty|in_list[active,archived]',
        'start_date' => 'permit_empty|valid_date',
        'end_date'   => 'permit_empty|valid_date',
    ];

    protected $validationMessages = [
        'patient_id' => [
            'required' => 'O paciente e obrigatorio.',
            'integer'  => 'Selecione um paciente valido.',
        ],
        'status' => [
            'in_list' => 'O status informado e invalido.',
        ],
        'start_date' => [
            'valid_date' => 'Informe uma data de inicio valida.',
        ],
        'end_date' => [
            'valid_date' => 'Informe uma data de fim valida.',
        ],
    ];
}
