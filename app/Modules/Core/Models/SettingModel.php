<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class SettingModel extends BaseModel
{
    protected $table         = 'settings';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'setting_key', 'setting_value'];

    /**
     * Retorna o valor de uma chave de configuracao para um tenant.
     */
    public function getValue(string $key, ?int $tenantId = null): ?string
    {
        $row = $this->db->table('settings')
            ->where('tenant_id', $tenantId)
            ->where('setting_key', $key)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row !== null ? (string) ($row['setting_value'] ?? '') : null;
    }

    /**
     * Cria ou atualiza o valor de uma chave de configuracao para um tenant.
     */
    public function setValue(string $key, string $value, ?int $tenantId = null): bool
    {
        $existing = $this->db->table('settings')
            ->where('tenant_id', $tenantId)
            ->where('setting_key', $key)
            ->get()
            ->getRowArray();

        $now = date('Y-m-d H:i:s');

        if ($existing !== null) {
            return $this->db->table('settings')
                ->where('id', (int) $existing['id'])
                ->update([
                    'setting_value' => $value,
                    'updated_at'    => $now,
                ]);
        }

        return $this->db->table('settings')->insert([
            'tenant_id'     => $tenantId,
            'setting_key'   => $key,
            'setting_value' => $value,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }
}
