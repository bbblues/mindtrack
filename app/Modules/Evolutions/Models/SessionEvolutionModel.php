<?php

namespace App\Modules\Evolutions\Models;

use App\Models\BaseModel;
use CodeIgniter\Database\BaseBuilder;

class SessionEvolutionModel extends BaseModel
{
    protected $table         = 'session_evolutions';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'appointment_id',
        'patient_id',
        'professional_id',
        'evolution_text',
    ];

    protected $validationRules = [
        'appointment_id'  => 'required|integer',
        'patient_id'      => 'required|integer',
        'professional_id' => 'required|integer',
        'evolution_text'  => 'required',
    ];

    protected $validationMessages = [
        'appointment_id' => [
            'required' => 'O agendamento e obrigatorio.',
            'integer'  => 'Agendamento invalido.',
        ],
        'evolution_text' => [
            'required' => 'O texto da evolucao e obrigatorio.',
        ],
    ];

    /**
     * Retorna um builder novo para a tabela de evolucoes, ja com os filtros
     * de tenant e soft delete aplicados. Evita que o estado da query vaze
     * entre chamadas consecutivas na mesma requisicao.
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
     * Lista as evolucoes de um paciente em ordem cronologica (mais recente
     * primeiro), incluindo o nome do profissional e a data da sessao.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forPatient(int $patientId): array
    {
        return $this->newQuery()
            ->select($this->table . '.*, appointments.start_time AS appointment_start_time, users.name AS professional_name')
            ->join('appointments', 'appointments.id = ' . $this->table . '.appointment_id', 'left')
            ->join('users', 'users.id = ' . $this->table . '.professional_id', 'left')
            ->where($this->table . '.patient_id', $patientId)
            ->orderBy($this->table . '.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }
}
