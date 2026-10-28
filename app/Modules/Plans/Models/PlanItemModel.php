<?php

namespace App\Modules\Plans\Models;

use App\Models\BaseModel;

class PlanItemModel extends BaseModel
{
    protected $table         = 'plan_items';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'therapeutic_plan_id',
        'protocol_item_id',
        'objective',
        'status',
        'sort_order',
    ];

    protected $validationRules = [
        'therapeutic_plan_id' => 'required|integer',
        'protocol_item_id'    => 'permit_empty|integer',
        'objective'           => 'permit_empty',
        'status'              => 'permit_empty|in_list[in_progress,acquired]',
        'sort_order'          => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'therapeutic_plan_id' => [
            'required' => 'O plano terapeutico e obrigatorio.',
            'integer'  => 'Plano terapeutico invalido.',
        ],
        'status' => [
            'in_list' => 'O status informado e invalido.',
        ],
    ];
}
