<?php

namespace App\Modules\Core\Models;

use App\Models\BaseModel;

class UserModel extends BaseModel
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tenant_id', 'name', 'email', 'password', 'is_super_admin', 'is_active'];

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected $validationRules = [
        'name'     => 'required|max_length[255]',
        'email'    => 'required|valid_email|max_length[255]|is_unique[users.email,id,{id}]',
        'password' => 'permit_empty|min_length[8]',
    ];

    protected $validationMessages = [
        'email' => [
            'is_unique' => 'Este e-mail ja esta em uso.',
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
}
