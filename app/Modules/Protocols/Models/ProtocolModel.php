<?php

namespace App\Modules\Protocols\Models;

use App\Models\BaseModel;

class ProtocolModel extends BaseModel
{
    protected $table         = 'protocols';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'name',
        'description',
        'is_active',
    ];

    protected $validationRules = [
        'name'        => 'required|max_length[255]',
        'description' => 'permit_empty',
        'is_active'   => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'name' => [
            'required'    => 'O nome do protocolo e obrigatorio.',
            'max_length'  => 'O nome do protocolo deve ter no maximo 255 caracteres.',
        ],
        'is_active' => [
            'in_list' => 'O status informado e invalido.',
        ],
    ];

    /**
     * Lista os protocolos paginados com busca por nome/descricao.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchPaginated(?string $search = null): array
    {
        $search = trim((string) $search);

        if ($search !== '') {
            $this
                ->groupStart()
                ->like('protocols.name', $search)
                ->orLike('protocols.description', $search)
                ->groupEnd();
        }

        return $this->orderBy('protocols.name', 'ASC')->paginate(10);
    }
}
