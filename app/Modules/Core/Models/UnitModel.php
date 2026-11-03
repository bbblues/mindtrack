<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class UnitModel extends BaseModel
{
    protected $table         = 'units';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'name', 'cnpj', 'is_active'];

    /**
     * Cria a unidade padrao "Matriz" para uma nova clinica (tenant).
     * Idempotente: nao duplica caso a unidade ja exista.
     */
    public function createDefaultUnitForTenant(int $tenantId): void
    {
        $exists = $this->db->table('units')
            ->where('tenant_id', $tenantId)
            ->where('name', 'Matriz')
            ->get()
            ->getRowArray();

        if ($exists !== null) {
            return;
        }

        if ($this->insert([
            'tenant_id' => $tenantId,
            'name'      => 'Matriz',
            'cnpj'      => null,
            'is_active' => 1,
        ]) === false) {
            log_message('error', 'Falha ao criar a unidade padrao "Matriz" para o tenant ' . $tenantId . '.');
        }
    }
}
