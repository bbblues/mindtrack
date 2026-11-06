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
        'status',
        'finalized_at',
        'finalized_by',
        'is_confidential',
    ];

    protected $validationRules = [
        'appointment_id'   => 'required|integer',
        'patient_id'       => 'required|integer',
        'professional_id'  => 'required|integer',
        'evolution_text'   => 'required',
        'status'           => 'permit_empty|in_list[draft,finalized]',
        'finalized_at'     => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'finalized_by'     => 'permit_empty|integer',
        'is_confidential'  => 'permit_empty|in_list[0,1]',
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
        $rows = $this->newQuery()
            ->select($this->table . '.*, appointments.start_time AS appointment_start_time, users.name AS professional_name')
            ->join('appointments', 'appointments.id = ' . $this->table . '.appointment_id', 'left')
            ->join('users', 'users.id = ' . $this->table . '.professional_id', 'left')
            ->where($this->table . '.patient_id', $patientId)
            ->orderBy($this->table . '.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return array_map(fn (array $row): array => $this->maskConfidentialText($row), $rows);
    }

    /**
     * Mascara o texto de uma evolucao sigilosa quando o usuario logado nao
     * e o autor (professional_id). Super Admin NAO tem excecao.
     *
     * @param array<string, mixed> $evolution
     *
     * @return array<string, mixed>
     */
    public function maskConfidentialText(array $evolution): array
    {
        if ((int) ($evolution['is_confidential'] ?? 0) === 1
            && (int) session('user_id') !== (int) ($evolution['professional_id'] ?? 0)) {
            $evolution['evolution_text'] = 'Nota confidencial';
        }

        return $evolution;
    }

    /**
     * Retorna a evolucao ativa (nao deletada) de um agendamento.
     *
     * @return array<string, mixed>|null
     */
    public function forAppointment(int $appointmentId): ?array
    {
        $row = $this->newQuery()
            ->where($this->table . '.appointment_id', $appointmentId)
            ->get()
            ->getRowArray();

        return $row !== null ? $row : null;
    }

    /**
     * Indica se a evolucao ja foi finalizada (imutavel).
     *
     * @param array<string, mixed>|null $evolution
     */
    public function isFinalized(?array $evolution): bool
    {
        return $evolution !== null && ($evolution['status'] ?? '') === 'finalized';
    }
}
