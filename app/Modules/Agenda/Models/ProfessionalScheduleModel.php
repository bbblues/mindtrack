<?php

namespace App\Modules\Agenda\Models;

use App\Models\BaseModel;

class ProfessionalScheduleModel extends BaseModel
{
    protected $table         = 'professional_schedules';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'professional_id', 'unit_id', 'room_id', 'day_of_week', 'start_time', 'end_time'];

    protected $validationRules = [
        'professional_id' => 'required|integer',
        'day_of_week'     => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[6]',
        'start_time'      => 'required|regex_match[/^\d{2}:\d{2}(:\d{2})?$/]',
        'end_time'        => 'required|regex_match[/^\d{2}:\d{2}(:\d{2})?$/]',
        'unit_id'         => 'permit_empty|integer',
        'room_id'         => 'permit_empty|integer',
    ];

    /**
     * Retorna os intervalos de um profissional para um dia da semana.
     *
     * @return list<array{day_of_week: int, start_time: string, end_time: string}>
     */
    public function forDay(int $professionalId, int $dayOfWeek): array
    {
        $rows = $this->where('professional_id', $professionalId)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time', 'ASC')
            ->findAll();

        return array_map(static fn (array $row): array => [
            'day_of_week' => (int) $row['day_of_week'],
            'start_time'  => substr((string) ($row['start_time'] ?? '00:00'), 0, 5),
            'end_time'    => substr((string) ($row['end_time'] ?? '00:00'), 0, 5),
        ], $rows);
    }

    /**
     * Retorna todos os intervalos de um profissional, agrupados por dia da semana.
     *
     * @return array<int, list<array{start_time: string, end_time: string}>>
     */
    public function groupedForProfessional(int $professionalId): array
    {
        $rows = $this->where('professional_id', $professionalId)
            ->orderBy('day_of_week', 'ASC')
            ->orderBy('start_time', 'ASC')
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $day = (int) $row['day_of_week'];
            $grouped[$day][] = [
                'start_time' => substr((string) ($row['start_time'] ?? '00:00'), 0, 5),
                'end_time'   => substr((string) ($row['end_time'] ?? '00:00'), 0, 5),
            ];
        }

        return $grouped;
    }

    /**
     * Substitui toda a agenda de um profissional por um novo conjunto de slots.
     *
     * @param list<array{day_of_week: int, start_time: string, end_time: string}> $slots
     */
    public function replaceForProfessional(int $professionalId, ?int $tenantId, array $slots): bool
    {
        $this->db->transStart();

        $this->db->table($this->table)
            ->where('professional_id', $professionalId)
            ->where('deleted_at', null)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        foreach ($slots as $slot) {
            $now = date('Y-m-d H:i:s');

            $inserted = $this->db->table($this->table)->insert([
                'tenant_id'       => $tenantId,
                'professional_id' => $professionalId,
                'unit_id'         => $slot['unit_id'] ?? null,
                'room_id'         => $slot['room_id'] ?? null,
                'day_of_week'     => (int) $slot['day_of_week'],
                'start_time'      => $slot['start_time'],
                'end_time'        => $slot['end_time'],
                'created_at'      => $now,
                'updated_at'      => $now,
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
