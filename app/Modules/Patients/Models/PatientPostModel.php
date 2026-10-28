<?php

namespace App\Modules\Patients\Models;

use App\Models\BaseModel;
use CodeIgniter\Database\BaseBuilder;

class PatientPostModel extends BaseModel
{
    /**
     * Tipos validos de publicacao no mural.
     *
     * @var array<string, string>
     */
    public const POST_TYPES = [
        'evolution'     => 'Evolução',
        'communication' => 'Comunicado',
        'family'        => 'Família',
        'school'        => 'Escola',
    ];

    protected $table         = 'patient_posts';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'patient_id',
        'user_id',
        'content',
        'type',
    ];

    protected $validationRules = [
        'patient_id' => 'required|integer',
        'user_id'    => 'required|integer',
        'content'    => 'required',
        'type'       => 'required|in_list[evolution,communication,family,school]',
    ];

    protected $validationMessages = [
        'content' => [
            'required' => 'O conteúdo da publicação é obrigatório.',
        ],
        'type' => [
            'required' => 'Selecione o tipo da publicação.',
            'in_list'  => 'Tipo de publicação inválido.',
        ],
    ];

    /**
     * Retorna um builder novo para a tabela de publicacoes, ja com os filtros
     * de tenant e soft delete aplicados. Evita que o estado da query vaze
     * entre chamadas consecutivas na mesma requisicao.
     */
    protected function newQuery(): BaseBuilder
    {
        $builder = $this->db->table($this->table);

        $tenantId = $this->currentTenantId();
        if ($tenantId !== null && $this->tableHasTenantColumn($this->table)) {
            $builder->where($this->table . '.tenant_id', $tenantId);
        }

        if ($this->useSoftDeletes) {
            $builder->where($this->table . '.deleted_at', null);
        }

        return $builder;
    }

    /**
     * Lista as publicacoes do mural de um paciente (mais recentes primeiro),
     * incluindo o nome e o cargo (role) de quem publicou.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forPatient(int $patientId, ?string $type = null): array
    {
        $builder = $this->newQuery()
            ->select($this->table . '.*, users.name AS user_name')
            ->join('users', 'users.id = ' . $this->table . '.user_id', 'left')
            ->where($this->table . '.patient_id', $patientId);

        if ($type !== null && $type !== '') {
            $builder->where($this->table . '.type', $type);
        }

        $posts = $builder
            ->orderBy($this->table . '.created_at', 'DESC')
            ->orderBy($this->table . '.id', 'DESC')
            ->get()
            ->getResultArray();

        return $this->attachAuthorRoles($posts);
    }

    /**
     * Busca uma publicacao incluindo o nome e o cargo do autor.
     *
     * @return array<string, mixed>|null
     */
    public function findWithAuthor(int $id): ?array
    {
        $post = $this->newQuery()
            ->select($this->table . '.*, users.name AS user_name')
            ->join('users', 'users.id = ' . $this->table . '.user_id', 'left')
            ->where($this->table . '.id', $id)
            ->get()
            ->getRowArray();

        if ($post === null) {
            return null;
        }

        $posts = $this->attachAuthorRoles([$post]);

        return $posts[0] ?? null;
    }

    /**
     * Anexa o nome dos cargos (roles) de cada autor a lista de publicacoes.
     *
     * @param array<int, array<string, mixed>> $posts
     *
     * @return array<int, array<string, mixed>>
     */
    private function attachAuthorRoles(array $posts): array
    {
        $userIds = array_values(array_unique(array_filter(array_map(
            static fn ($post) => (int) ($post['user_id'] ?? 0),
            $posts
        ))));

        $roleMap = [];
        if ($userIds !== []) {
            $rows = $this->db->table('role_user')
                ->select('role_user.user_id, roles.name AS role_name')
                ->join('roles', 'roles.id = role_user.role_id', 'left')
                ->whereIn('role_user.user_id', $userIds)
                ->where('roles.deleted_at', null)
                ->orderBy('roles.name', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $roleMap[(int) $row['user_id']][] = (string) $row['role_name'];
            }
        }

        foreach ($posts as &$post) {
            $roles    = $roleMap[(int) ($post['user_id'] ?? 0)] ?? [];
            $post['user_role'] = $roles !== [] ? implode(', ', $roles) : 'Profissional';
        }
        unset($post);

        return $posts;
    }
}
