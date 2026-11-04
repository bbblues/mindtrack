<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

/**
 * Pivo entre papéis (roles) e permissões (permissions).
 *
 * Segue o padrão do RoleUserModel: sem soft delete e sem timestamps
 * (o vinculo é regenerado a cada sincronização e a tabela possui apenas
 * created_at).
 */
class PermissionRoleModel extends BaseModel
{
    protected $table          = 'permission_role';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = ['permission_id', 'role_id'];

    /**
     * Sincroniza as permissões de um papel (remove as atuais e insere as novas).
     *
     * @param list<mixed> $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $permissionIds = array_values(array_unique(array_filter(array_map('intval', $permissionIds))));

        $this->where('role_id', $roleId)->delete();

        foreach ($permissionIds as $permissionId) {
            $this->insert([
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    /**
     * Ids das permissões vinculadas a um papel.
     *
     * @return list<int>
     */
    public function permissionIdsForRole(int $roleId): array
    {
        return array_map('intval', array_column(
            $this->select('permission_id')->where('role_id', $roleId)->findAll(),
            'permission_id'
        ));
    }

    /**
     * Slugs das permissões do usuário (união das permissões de todos os
     * seus papéis), já filtrando registros removidos via soft delete.
     *
     * @return list<string>
     */
    public function slugsForUser(int $userId): array
    {
        $rows = $this->db->table('permission_role')
            ->select('permissions.slug')
            ->join('roles', 'roles.id = permission_role.role_id', 'left')
            ->join('role_user', 'role_user.role_id = roles.id', 'left')
            ->join('permissions', 'permissions.id = permission_role.permission_id', 'left')
            ->where('role_user.user_id', $userId)
            ->where('roles.deleted_at', null)
            ->where('permissions.deleted_at', null)
            ->distinct()
            ->get()
            ->getResultArray();

        return array_values(array_filter(array_map('strval', array_column($rows, 'slug'))));
    }
}
