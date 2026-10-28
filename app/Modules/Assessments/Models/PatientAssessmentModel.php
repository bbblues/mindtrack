<?php

namespace App\Modules\Assessments\Models;

use App\Models\BaseModel;

class PatientAssessmentModel extends BaseModel
{
    protected $table         = 'patient_assessments';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'patient_id',
        'protocol_id',
        'score',
        'status',
    ];

    protected $validationRules = [
        'patient_id'  => 'required|integer',
        'protocol_id' => 'required|integer',
        'score'       => 'permit_empty|integer',
        'status'      => 'permit_empty|in_list[in_progress,completed]',
    ];

    protected $validationMessages = [
        'patient_id' => [
            'required' => 'O paciente e obrigatorio.',
            'integer'  => 'Selecione um paciente valido.',
        ],
        'protocol_id' => [
            'required' => 'O protocolo e obrigatorio.',
            'integer'  => 'Selecione um protocolo valido.',
        ],
        'status' => [
            'in_list' => 'O status informado e invalido.',
        ],
    ];
}
