<?php

namespace App\Modules\Agenda\Models;

use App\Models\BaseModel;

class ClinicScheduleModel extends BaseModel
{
    protected $table         = 'clinic_schedules';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'day_of_week', 'start_time', 'end_time'];

    protected $validationRules = [
        'day_of_week' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[6]',
        'start_time'  => 'required|regex_match[/^\d{2}:\d{2}(:\d{2})?$/]',
        'end_time'    => 'required|regex_match[/^\d{2}:\d{2}(:\d{2})?$/]',
    ];

    /**
     * Retorna todos os turnos do tenant, ordenados por dia da semana.
     *
     * @return list<array{day_of_week: int, start_time: string, end_time: string}>
     */
    public function allForTenant(?int $tenantId): array
    {
        $rows = $this->db->table($this->table)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->orderBy('day_of_week', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): array => [
            'day_of_week' => (int) $row['day_of_week'],
            'start_time'  => substr((string) ($row['start_time'] ?? '00:00'), 0, 5),
            'end_time'    => substr((string) ($row['end_time'] ?? '00:00'), 0, 5),
        ], $rows);
    }

    /**
     * Retorna os turnos do tenant indexados por dia da semana (um turno por dia).
     *
     * @return array<int, array{start_time: string, end_time: string}>
     */
    public function groupedForTenant(?int $tenantId): array
    {
        $grouped = [];

        foreach ($this->allForTenant($tenantId) as $interval) {
            $day = (int) $interval['day_of_week'];
            $grouped[$day] = [
                'start_time' => $interval['start_time'],
                'end_time'   => $interval['end_time'],
            ];
        }

        return $grouped;
    }

    /**
     * Substitui todo o horário de funcionamento do tenant por um novo conjunto.
     *
     * @param list<array{day_of_week: int, start_time: string, end_time: string}> $slots
     */
    public function replaceForTenant(?int $tenantId, array $slots): bool
    {
        $this->db->transStart();

        $this->db->table($this->table)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        foreach ($slots as $slot) {
            $now = date('Y-m-d H:i:s');

            $inserted = $this->db->table($this->table)->insert([
                'tenant_id'   => $tenantId,
                'day_of_week' => (int) $slot['day_of_week'],
                'start_time'  => $slot['start_time'],
                'end_time'    => $slot['end_time'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            if (! $inserted) {
                $this->db->transRollback();

                return false;
            }
        }

        $this->db->transComplete();

        return $this->db->transStatus() !== false;
    }
}
