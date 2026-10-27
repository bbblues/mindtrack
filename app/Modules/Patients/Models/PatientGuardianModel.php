<?php

namespace App\Modules\Patients\Models;

use App\Models\BaseModel;

/**
 * Pivo entre pacientes e responsaveis.
 *
 * Optamos por nao usar soft delete aqui porque o vinculo e regenerado
 * a cada salvamento e a chave unica (patient_id, guardian_id) precisa
 * permitir a re-insercao do mesmo par.
 */
class PatientGuardianModel extends BaseModel
{
    protected $table           = 'patient_guardians';
    protected $primaryKey      = 'id';
    protected $useSoftDeletes  = false;
    protected $allowedFields   = [
        'tenant_id',
        'patient_id',
        'guardian_id',
        'is_primary',
        'is_financial',
    ];
}
