<?php

namespace App\Modules\Assessments\Models;

use App\Models\BaseModel;
use CodeIgniter\Database\BaseBuilder;

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
        'score'       => 'permit_empty|decimal',
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

    /**
     * Retorna um builder novo para a tabela de avaliacoes, ja com os filtros
     * de tenant e soft delete aplicados.
     */
    protected function newQuery(): BaseBuilder
    {
        $builder = $this->db->table($this->table);

        $tenantId = $this->currentTenantId();
        if ($tenantId !== null && $this->tableHasTenantColumn($this->table)) {
            $builder->where($this->table . '.tenant_id', $tenantId);
        }

        if ($this->useSoftDeletes) {
            $builder->where($this->table . '.deleted_at', null);
        }

        return $builder;
    }

    /**
     * Lista as avaliacoes de um paciente (mais recentes primeiro), incluindo
     * o nome do protocolo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forPatient(int $patientId): array
    {
        return $this->newQuery()
            ->select($this->table . '.*, protocols.name AS protocol_name')
            ->join('protocols', 'protocols.id = ' . $this->table . '.protocol_id', 'left')
            ->where($this->table . '.patient_id', $patientId)
            ->orderBy($this->table . '.created_at', 'DESC')
            ->orderBy($this->table . '.id', 'DESC')
            ->get()
            ->getResultArray();
    }
}
