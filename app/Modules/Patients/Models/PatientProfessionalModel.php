<?php

namespace App\Modules\Patients\Models;

use App\Models\BaseModel;

/**
 * Pivo entre pacientes e profissionais (muitos-para-muitos).
 *
 * Um paciente pode ser atendido por vários profissionais (Fono, TO, Psico,
 * ABA). O campo `is_primary` marca o profissional responsável principal.
 */
class PatientProfessionalModel extends BaseModel
{
    protected $table          = 'patient_professionals';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields  = ['tenant_id', 'patient_id', 'user_id', 'is_primary'];

    /**
     * Sincroniza os profissionais vinculados a um paciente.
     *
     * @param list<mixed> $userIds
     */
    public function syncProfessionals(int $patientId, ?int $tenantId, array $userIds, ?int $primaryUserId): void
    {
        $userIds       = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn (int $id): bool => $id > 0)));
        $primaryUserId = $primaryUserId !== null ? (int) $primaryUserId : null;

        $this->where('patient_id', $patientId)->delete();

        foreach ($userIds as $userId) {
            $this->insert([
                'tenant_id'  => $tenantId,
                'patient_id' => $patientId,
                'user_id'    => $userId,
                'is_primary' => ($primaryUserId !== null && $primaryUserId === $userId) ? 1 : 0,
            ]);
        }
    }

    /**
     * Ids dos profissionais vinculados a um paciente.
     *
     * @return list<int>
     */
    public function professionalIdsForPatient(int $patientId): array
    {
        return array_map('intval', array_column(
            $this->select('user_id')->where('patient_id', $patientId)->findAll(),
            'user_id'
        ));
    }

    /**
     * Id do profissional responsável principal (ou null).
     */
    public function primaryProfessionalIdForPatient(int $patientId): ?int
    {
        $row = $this
            ->select('user_id')
            ->where('patient_id', $patientId)
            ->where('is_primary', 1)
            ->first();

        return $row !== null ? (int) $row['user_id'] : null;
    }
}
