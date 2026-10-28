<?php

namespace App\Modules\Patients\Models;

use App\Models\BaseModel;
use CodeIgniter\Database\BaseBuilder;

class PatientMilestoneModel extends BaseModel
{
    /**
     * Icones disponiveis para os marcos da jornada.
     *
     * @var array<string, string>
     */
    public const MILESTONE_ICONS = [
        '🎯'      => 'Objetivo',
        '⭐'      => 'Conquista',
        '📈'      => 'Evolução',
        '🗣️'     => 'Comunicação',
        '👨‍👩‍👧' => 'Família',
        '🏫'      => 'Escola',
        '🩺'      => 'Saúde',
        '💬'      => 'Outro',
    ];

    protected $table         = 'patient_milestones';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'patient_id',
        'title',
        'description',
        'event_date',
        'icon',
    ];

    protected $validationRules = [
        'patient_id'  => 'required|integer',
        'title'       => 'required|max_length[255]',
        'description' => 'permit_empty',
        'event_date'  => 'required|valid_date',
        'icon'        => 'permit_empty|max_length[50]',
    ];

    protected $validationMessages = [
        'title' => [
            'required' => 'O título do marco é obrigatório.',
        ],
        'event_date' => [
            'required'   => 'A data do marco é obrigatória.',
            'valid_date' => 'Informe uma data válida.',
        ],
    ];

    /**
     * Retorna um builder novo para a tabela de marcos, ja com os filtros de
     * tenant e soft delete aplicados.
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
     * Lista os marcos da jornada de um paciente (mais recentes primeiro).
     *
     * @return array<int, array<string, mixed>>
     */
    public function forPatient(int $patientId): array
    {
        return $this->newQuery()
            ->where($this->table . '.patient_id', $patientId)
            ->orderBy($this->table . '.event_date', 'DESC')
            ->orderBy($this->table . '.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Busca um marco do paciente (respeitando tenant e soft delete).
     *
     * @return array<string, mixed>|null
     */
    public function findForPatient(int $id): ?array
    {
        return $this->newQuery()
            ->where($this->table . '.id', $id)
            ->get()
            ->getRowArray();
    }
}
