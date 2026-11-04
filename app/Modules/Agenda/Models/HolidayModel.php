<?php

namespace App\Modules\Agenda\Models;

use App\Models\BaseModel;

class HolidayModel extends BaseModel
{
    protected $table         = 'holidays';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'city_code', 'state', 'name', 'date', 'scope', 'badge', 'color', 'source'];

    protected $validationRules = [
        'name'  => 'required|max_length[255]',
        'date'  => 'required|valid_date[Y-m-d]',
        'scope' => 'required|in_list[national,state,municipal,closure]',
        'badge' => 'required|max_length[30]',
        'color' => 'required|max_length[7]',
    ];

    /**
     * Feriados (globais + do tenant) que caem dentro de um intervalo de datas.
     *
     * @return list<array<string, mixed>>
     */
    public function inRange(string $startDate, string $endDate, ?int $tenantId): array
    {
        $builder = $this->db->table($this->table)
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->where('deleted_at', null)
            ->orderBy('date', 'ASC')
            ->orderBy('scope', 'ASC');

        $this->applyTenantScope($builder, $tenantId);

        return $builder->get()->getResultArray();
    }

    /**
     * Feriados (globais + do tenant) de uma data específica.
     *
     * @return list<array<string, mixed>>
     */
    public function forDate(string $date, ?int $tenantId): array
    {
        $builder = $this->db->table($this->table)
            ->where('date', $date)
            ->where('deleted_at', null)
            ->orderBy('scope', 'ASC');

        $this->applyTenantScope($builder, $tenantId);

        return $builder->get()->getResultArray();
    }

    /**
     * Feriados nacionais (globais) de um ano.
     *
     * @return list<array<string, mixed>>
     */
    public function nationalForYear(int $year): array
    {
        return $this->db->table($this->table)
            ->where('scope', 'national')
            ->where('tenant_id', null)
            ->where('deleted_at', null)
            ->where('date >=', $year . '-01-01')
            ->where('date <=', $year . '-12-31')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Feriados locais / datas de fechamento cadastrados manualmente pelo tenant.
     *
     * @return list<array<string, mixed>>
     */
    public function manualForTenant(?int $tenantId): array
    {
        return $this->db->table($this->table)
            ->where('source', 'manual')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Busca um feriado manual pelo id (escopado ao tenant).
     *
     * @return array<string, mixed>|null
     */
    public function manualById(int $id, ?int $tenantId): ?array
    {
        $row = $this->db->table($this->table)
            ->where('id', $id)
            ->where('source', 'manual')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row !== null ? $row : null;
    }

    /**
     * Cria/atualiza um feriado nacional vindo da BrasilAPI (idempotente).
     */
    public function upsertNational(string $date, string $name, string $badge, string $color): bool
    {
        $existing = $this->db->table($this->table)
            ->where('date', $date)
            ->where('scope', 'national')
            ->where('tenant_id', null)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $now = date('Y-m-d H:i:s');

        if ($existing !== null) {
            return $this->db->table($this->table)
                ->where('id', (int) $existing['id'])
                ->update([
                    'name'       => $name,
                    'badge'      => $badge,
                    'color'      => $color,
                    'updated_at' => $now,
                ]);
        }

        return $this->db->table($this->table)->insert([
            'tenant_id'  => null,
            'name'       => $name,
            'date'       => $date,
            'scope'      => 'national',
            'badge'      => $badge,
            'color'      => $color,
            'source'     => 'brasilapi',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function insertManual(array $data): bool
    {
        $now = date('Y-m-d H:i:s');

        return $this->db->table($this->table)->insert(array_merge($data, [
            'source'     => 'manual',
            'created_at' => $now,
            'updated_at' => $now,
        ]));
    }

    public function updateManual(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->table($this->table)->where('id', $id)->update($data);
    }

    public function deleteManual(int $id): bool
    {
        return $this->db->table($this->table)->where('id', $id)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function applyTenantScope($builder, ?int $tenantId): void
    {
        $builder->groupStart()
            ->where('tenant_id', null);

        if ($tenantId !== null) {
            $builder->orWhere('tenant_id', $tenantId);
        }

        $builder->groupEnd();
    }
}
