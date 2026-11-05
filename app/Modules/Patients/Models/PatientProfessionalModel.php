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

        // Garante que o Responsável Principal sempre seja vinculado,
        // independentemente de ter sido marcado na lista de assistentes.
        if ($primaryUserId !== null && $primaryUserId > 0 && ! in_array($primaryUserId, $userIds, true)) {
            $userIds[] = $primaryUserId;
        }

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
     * Vincula um profissional ao paciente sem remover os demais vínculos.
     *
     * Regras do auto-link:
     *  - Se o par (patient_id, user_id) já existe, não faz nada (não altera o
     *    is_primary existente).
     *  - Senão, insere com is_primary = true se ainda NÃO existe um responsável
     *    principal para o paciente; caso contrário, insere como assistente
     *    (is_primary = false).
     */
    public function linkProfessional(int $patientId, int $userId, ?int $tenantId): bool
    {
        if ($patientId <= 0 || $userId <= 0) {
            return false;
        }

        $existing = $this->where('patient_id', $patientId)
            ->where('user_id', $userId)
            ->first();

        if ($existing !== null) {
            return true;
        }

        $isPrimary = $this->primaryProfessionalIdForPatient($patientId) === null ? 1 : 0;

        try {
            return $this->insert([
                'tenant_id'  => $tenantId,
                'patient_id' => $patientId,
                'user_id'    => $userId,
                'is_primary' => $isPrimary,
            ]) !== false;
        } catch (\Throwable $e) {
            log_message('error', 'Falha ao vincular profissional ao paciente: ' . $e->getMessage());

            return false;
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
