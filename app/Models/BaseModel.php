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
     * Quando true, o filtro de tenant passa a incluir tambem registros
     * globais (tenant_id IS NULL) — usado para catalogos compartilhados
     * (ex: protocolos complexos importados via Seeder).
     */
    protected $includeGlobalRows = false;

    /**
     * Cache da verificacao da coluna tenant_id por tabela.
     *
     * @var array<string, bool>
     */
    protected static array $tenantColumnCache = [];

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

        // IMPORTANTE: o Query Builder compartilhado pelo Model limpa as
        // clausulas WHERE apos cada execucao (BaseBuilder::get -> resetSelect).
        // Por isso o filtro de tenant NAO pode ser cacheado: ele precisa ser
        // reaplicado em toda chamada a builder(), caso contrario a consulta
        // seguinte vazaria registros de outros tenants.
        if ($this->includeGlobalRows) {
            $builder
                ->groupStart()
                ->where($tableName . '.tenant_id', $tenantId)
                ->orWhere($tableName . '.tenant_id', null)
                ->groupEnd();
        } else {
            $builder->where($tableName . '.tenant_id', $tenantId);
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
