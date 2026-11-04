<?php

namespace App\Libraries;

use App\Modules\Core\Models\PermissionModel;
use App\Modules\Core\Models\RoleModel;
use App\Modules\Core\Models\TenantModel;

/**
 * Sincroniza o catálogo de permissões no banco e aplica o conjunto padrão
 * aos papéis padrão de todos os tenants.
 */
class RbacSynchronizer
{
    /**
     * Insere/atualiza as permissões do catálogo (idempotente por slug).
     *
     * @return int Quantidade de permissões sincronizadas.
     */
    public function syncCatalog(): int
    {
        $permissionModel = new PermissionModel();
        $count           = 0;

        foreach (PermissionCatalog::all() as $item) {
            $slug = (string) ($item['slug'] ?? '');

            if ($slug === '') {
                continue;
            }

            $data = [
                'name'        => (string) ($item['name'] ?? $slug),
                'slug'        => $slug,
                'module'      => (string) ($item['module'] ?? ''),
                'description' => $item['description'] ?? null,
                'scope'       => isset($item['scope']) ? (string) $item['scope'] : null,
                'sort_order'  => (int) ($item['sort_order'] ?? 0),
            ];

            $existing = $permissionModel->where('slug', $slug)->first();

            if ($existing === null) {
                $permissionModel->insert($data);
            } else {
                $permissionModel->update((int) $existing['id'], $data);
            }

            $count++;
        }

        return $count;
    }

    /**
     * Garante os papéis padrão e suas permissões em todos os tenants.
     */
    public function syncAllTenants(): void
    {
        $roleModel = new RoleModel();
        $tenants   = (new TenantModel())->findAll();

        foreach ($tenants as $tenant) {
            $roleModel->createDefaultRolesForTenant((int) $tenant['id']);
        }
    }
}
