<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

/**
 * Pivo entre usuarios e cargos (roles).
 *
 * Segue o mesmo padrao do PatientGuardianModel: sem soft delete e sem
 * timestamps, pois o vinculo e regenerado a cada salvamento e a tabela
 * possui apenas created_at.
 */
class RoleUserModel extends BaseModel
{
    protected $table          = 'role_user';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;
    protected $allowedFields  = ['role_id', 'user_id'];

    /**
     * Sincroniza os cargos de um usuario (remove os atuais e insere os novos).
     *
     * @param list<mixed> $roleIds
     */
    public function syncRoles(int $userId, array $roleIds): void
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds))));

        $this->where('user_id', $userId)->delete();

        foreach ($roleIds as $roleId) {
            $this->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);
        }
    }

    /**
     * Ids dos cargos vinculados a um usuario.
     *
     * @return list<int>
     */
    public function roleIdsForUser(int $userId): array
    {
        return array_map('intval', array_column(
            $this->select('role_id')->where('user_id', $userId)->findAll(),
            'role_id'
        ));
    }
}
