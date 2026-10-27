<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * BaseModel
 *
 * Base compartilhada por todas as Models do sistema.
 * Aplica automaticamente:
 *  - Soft Deletes;
 *  - Timestamps;
 *  - Isolamento multi-tenant (filtro por tenant_id quando a tabela
 *    possui essa coluna e existe um tenant na sessao).
 */
abstract class BaseModel extends Model
{
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $returnType     = 'array';
    protected $protectFields  = true;

    /**
     * Cache da verificacao da coluna tenant_id por tabela.
     *
     * @var array<string, bool>
     */
    protected static array $tenantColumnCache = [];

    /**
     * Controla se o filtro de tenant ja foi aplicado no builder da tabela.
     *
     * @var array<string, bool>
     */
    protected array $tenantScopeApplied = [];

    /**
     * Retorna o tenant_id do usuario logado.
     * Super Admin (tenant_id = null) e execucoes CLI nao filtram.
     */
    protected function currentTenantId(): ?int
    {
        if (is_cli()) {
            return null;
        }

        $tenantId = session('tenant_id');

        return is_numeric($tenantId) ? (int) $tenantId : null;
    }

    /**
     * {@inheritDoc}
     */
    public function builder(?string $table = null)
    {
        $builder = parent::builder($table);

        $tableName = $table ?? $this->table;
        $tenantId  = $this->currentTenantId();

        if ($tenantId === null || ! $this->tableHasTenantColumn($tableName)) {
            return $builder;
        }

        // O builder da propria tabela e compartilhado/cacheado pelo Model.
        if ($tableName === $this->table && ($this->tenantScopeApplied[$tableName] ?? false)) {
            return $builder;
        }

        $builder->where($tableName . '.tenant_id', $tenantId);

        if ($tableName === $this->table) {
            $this->tenantScopeApplied[$tableName] = true;
        }

        return $builder;
    }

    /**
     * Verifica (com cache) se uma tabela possui a coluna tenant_id.
     */
    protected function tableHasTenantColumn(string $table): bool
    {
        if (! array_key_exists($table, static::$tenantColumnCache)) {
            static::$tenantColumnCache[$table] = $this->db->fieldExists('tenant_id', $table);
        }

        return static::$tenantColumnCache[$table];
    }
}
