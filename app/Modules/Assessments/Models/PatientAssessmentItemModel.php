<?php

namespace App\Modules\Assessments\Models;

use App\Models\BaseModel;

class PatientAssessmentItemModel extends BaseModel
{
    protected $table         = 'patient_assessment_items';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'assessment_id',
        'protocol_item_id',
        'status_id',
        'value_at_time',
    ];

    protected $validationRules = [
        'assessment_id'   => 'required|integer',
        'protocol_item_id' => 'required|integer',
        'status_id'       => 'permit_empty|integer',
        'value_at_time'   => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'assessment_id' => [
            'required' => 'A avaliacao e obrigatoria.',
            'integer'  => 'Avaliacao invalida.',
        ],
        'protocol_item_id' => [
            'required' => 'O item do protocolo e obrigatorio.',
            'integer'  => 'Item de protocolo invalido.',
        ],
    ];
}
