<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class UserModel extends BaseModel
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'unit_id', 'name', 'email', 'password', 'is_super_admin', 'is_active'];

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected $validationRules = [
        'name'     => 'required|max_length[255]',
        'email'    => 'required|valid_email|max_length[255]',
        'password' => 'permit_empty|min_length[8]',
        'unit_id'  => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'unit_id' => [
            'integer' => 'Selecione uma unidade valida.',
        ],
    ];

    /**
     * Gera o hash da senha antes de persistir o usuario.
     */
    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password']) && $data['data']['password'] !== '') {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }

        return $data;
    }

    /**
     * Verifica se ja existe um usuario ativo com o e-mail informado.
     *
     * Consulta a tabela diretamente para ignorar o tenant scope do BaseModel
     * (a unicidade do e-mail de login e global) e para ignorar registros
     * removidos via soft delete. Permite excluir o proprio registro na edicao.
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $builder = $this->db->table('users')
            ->select('1')
            ->where('email', $email)
            ->where('deleted_at', null);

        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->get()->getRow() !== null;
    }

    /**
     * Lista os usuarios paginados incluindo o nome da unidade.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paginateWithUnit(?string $search = null): array
    {
        $this
            ->select('users.*, units.name AS unit_name')
            ->join('units', 'units.id = users.unit_id', 'left');

        $search = trim((string) $search);
        if ($search !== '') {
            $this
                ->groupStart()
                ->like('users.name', $search)
                ->orLike('users.email', $search)
                ->groupEnd();
        }

        return $this->orderBy('users.name', 'ASC')->paginate(10);
    }

    /**
     * Mapa [user_id => lista de cargos] para exibicao na listagem.
     *
     * @param list<int> $userIds
     *
     * @return array<int, list<array<string, mixed>>>
     */
    public function rolesForUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = $this->db->table('role_user')
            ->select('role_user.user_id, roles.id AS role_id, roles.name AS role_name, roles.slug AS role_slug')
            ->join('roles', 'roles.id = role_user.role_id', 'left')
            ->whereIn('role_user.user_id', $userIds)
            ->orderBy('roles.name', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['user_id']][] = $row;
        }

        return $map;
    }

    /**
     * Slugs dos cargos vinculados a um usuario (para verificacao de permissao).
     *
     * @return list<string>
     */
    public function roleSlugsForUser(int $userId): array
    {
        $rows = $this->db->table('role_user')
            ->select('roles.slug')
            ->join('roles', 'roles.id = role_user.role_id', 'left')
            ->where('role_user.user_id', $userId)
            ->where('roles.deleted_at', null)
            ->get()
            ->getResultArray();

        return array_values(array_filter(array_map('strval', array_column($rows, 'slug'))));
    }
}
