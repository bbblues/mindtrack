<?php

namespace App\Modules\Agenda\Models;

use App\Models\BaseModel;
use CodeIgniter\Database\BaseBuilder;

class AppointmentModel extends BaseModel
{
    protected $table         = 'appointments';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'patient_id',
        'professional_id',
        'room_id',
        'unit_id',
        'start_time',
        'end_time',
        'status',
        'is_recurring',
        'recurrence_group_id',
        'notes',
    ];

    protected $validationRules = [
        'patient_id'          => 'required|integer',
        'professional_id'     => 'required|integer',
        'unit_id'             => 'required|integer',
        'room_id'             => 'permit_empty|integer',
        'start_time'          => 'required|valid_date[Y-m-d H:i:s]',
        'end_time'            => 'required|valid_date[Y-m-d H:i:s]',
        'status'              => 'permit_empty|in_list[agendado,confirmado,realizado,falta_paciente,falta_profissional,cancelado]',
        'is_recurring'        => 'permit_empty|in_list[0,1]',
        'recurrence_group_id' => 'permit_empty|max_length[36]',
        'notes'               => 'permit_empty',
    ];

    protected $validationMessages = [
        'patient_id' => [
            'required' => 'O paciente e obrigatorio.',
            'integer'  => 'Selecione um paciente valido.',
        ],
        'professional_id' => [
            'required' => 'O profissional e obrigatorio.',
            'integer'  => 'Selecione um profissional valido.',
        ],
        'unit_id' => [
            'required' => 'A unidade e obrigatoria.',
            'integer'  => 'Selecione uma unidade valida.',
        ],
        'start_time' => [
            'required'   => 'O horario de inicio e obrigatorio.',
            'valid_date' => 'Informe um horario de inicio valido.',
        ],
        'end_time' => [
            'required'   => 'O horario de fim e obrigatorio.',
            'valid_date' => 'Informe um horario de fim valido.',
        ],
    ];

    /**
     * Retorna um builder novo para a tabela de agendamentos, ja com os
     * filtros de tenant e soft delete aplicados.
     *
     * Usado em vez do builder compartilhado do Model para evitar que o
     * estado da query vaze entre chamadas consecutivas na mesma requisicao.
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
     * Lista os agendamentos de uma data incluindo o nome do paciente e do
     * profissional para exibicao na grade.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forDate(string $date): array
    {
        return $this->newQuery()
            ->select($this->table . '.*, patients.name AS patient_name, users.name AS professional_name')
            ->join('patients', 'patients.id = ' . $this->table . '.patient_id', 'left')
            ->join('users', 'users.id = ' . $this->table . '.professional_id', 'left')
            ->where($this->table . '.start_time >=', $date . ' 00:00:00')
            ->where($this->table . '.start_time <=', $date . ' 23:59:59')
            ->orderBy($this->table . '.start_time', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Lista os agendamentos que fazem interseccao com o intervalo informado,
     * incluindo os nomes de paciente e profissional para exibicao no FullCalendar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forRange(?string $start = null, ?string $end = null): array
    {
        $builder = $this->newQuery()
            ->select($this->table . '.*, patients.name AS patient_name, users.name AS professional_name')
            ->join('patients', 'patients.id = ' . $this->table . '.patient_id', 'left')
            ->join('users', 'users.id = ' . $this->table . '.professional_id', 'left')
            ->orderBy($this->table . '.start_time', 'ASC');

        if ($start !== null && $end !== null) {
            $builder
                ->where($this->table . '.start_time <', $end)
                ->where($this->table . '.end_time >', $start);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Busca agendamentos conflitantes para um profissional no intervalo
     * [startTime, endTime). Agendamentos cancelados nao bloqueiam.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findConflicts(int $professionalId, string $startTime, string $endTime, ?int $excludeId = null): array
    {
        $builder = $this->newQuery()
            ->select($this->table . '.*, patients.name AS patient_name')
            ->join('patients', 'patients.id = ' . $this->table . '.patient_id', 'left')
            ->where($this->table . '.professional_id', $professionalId)
            ->where($this->table . '.start_time <', $endTime)
            ->where($this->table . '.end_time >', $startTime)
            ->whereNotIn($this->table . '.status', ['cancelado']);

        if ($excludeId !== null) {
            $builder->where($this->table . '.id !=', $excludeId);
        }

        return $builder->get()->getResultArray();
    }
}
