<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\RoleModel;
use CodeIgniter\HTTP\ResponseInterface;

class RoleController extends BaseController
{
    protected RoleModel $roleModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
    }

    /**
     * Lista os cargos do tenant do usuario logado.
     */
    public function index(): string
    {
        $roles = $this->roleModel->orderBy('name', 'ASC')->findAll();

        $roleRows = array_map(fn (array $role): array => $this->serializeRole($role), $roles);

        return view('App\Modules\Core\Views\roles\index', [
            'title'    => 'Cargos e Papéis',
            'roleRows' => $roleRows,
        ]);
    }

    /**
     * Cria um novo cargo vinculado ao tenant logado.
     */
    public function store(): ResponseInterface
    {
        $name = trim((string) $this->request->getPost('name'));

        if (! $this->validateData(['name' => $name], $this->nameRules(), $this->nameMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        $tenantId = $this->currentTenantId();
        $slug     = $this->slugFromName($name);

        if ($this->roleModel->where('slug', $slug)->where('tenant_id', $tenantId)->first() !== null) {
            return $this->respondError(['name' => 'Já existe um cargo com este nome na sua clínica.']);
        }

        $roleId = $this->roleModel->insert([
            'tenant_id'   => $tenantId,
            'name'        => $name,
            'slug'        => $slug,
            'description' => null,
            'is_active'   => 1,
        ]);

        if ($roleId === false) {
            return $this->respondError($this->roleModel->errors());
        }

        $role = $this->roleModel->find((int) $roleId);

        return $this->respondSuccess('Cargo criado com sucesso.', $role !== null ? $this->serializeRole($role) : null);
    }

    /**
     * Atualiza o nome do cargo.
     */
    public function update($id = null): ResponseInterface
    {
        $role = $this->roleModel->find((int) $id);

        if ($role === null) {
            return $this->respondError([], 'Cargo não encontrado.', 404);
        }

        $name = trim((string) $this->request->getPost('name'));

        if (! $this->validateData(['name' => $name], $this->nameRules(), $this->nameMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        $tenantId  = $this->currentTenantId();
        $isDefault = $this->roleModel->isDefaultSlug((string) $role['slug']);
        $slug      = $isDefault ? (string) $role['slug'] : $this->slugFromName($name);

        $duplicate = $this->roleModel
            ->where('slug', $slug)
            ->where('tenant_id', $tenantId)
            ->where('id !=', (int) $role['id'])
            ->first();

        if ($duplicate !== null) {
            return $this->respondError(['name' => 'Já existe um cargo com este nome na sua clínica.']);
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
        ];

        if (! $this->roleModel->update((int) $role['id'], $data)) {
            return $this->respondError($this->roleModel->errors());
        }

        $updated = $this->roleModel->find((int) $role['id']);

        return $this->respondSuccess('Cargo atualizado com sucesso.', $updated !== null ? $this->serializeRole($updated) : null);
    }

    /**
     * Remove o cargo (soft delete). Cargos padrao nao podem ser excluidos.
     */
    public function destroy($id = null): ResponseInterface
    {
        $role = $this->roleModel->find((int) $id);

        if ($role === null) {
            return $this->respondError([], 'Cargo não encontrado.', 404);
        }

        if ($this->roleModel->isDefaultSlug((string) $role['slug'])) {
            return $this->respondError([], 'Os cargos padrão do sistema não podem ser excluídos.', 422);
        }

        if (! $this->roleModel->delete((int) $role['id'])) {
            return $this->respondError($this->roleModel->errors(), 'Não foi possível excluir o cargo.');
        }

        return $this->respondSuccess('Cargo excluído com sucesso.');
    }

    /**
     * @return array<string, string>
     */
    private function nameRules(): array
    {
        return ['name' => 'required|max_length[100]'];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function nameMessages(): array
    {
        return [
            'name' => [
                'required'   => 'Informe o nome do cargo.',
                'max_length' => 'O nome do cargo deve ter no máximo 100 caracteres.',
            ],
        ];
    }

    private function currentTenantId(): ?int
    {
        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    private function slugFromName(string $name): string
    {
        $transliterated = function_exists('iconv')
            ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name)
            : $name;

        if ($transliterated === false) {
            $transliterated = $name;
        }

        $slug = strtolower((string) $transliterated);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'cargo';
    }

    /**
     * @param array<string, mixed> $role
     *
     * @return array<string, mixed>
     */
    private function serializeRole(array $role): array
    {
        return [
            'id'         => (int) $role['id'],
            'name'       => (string) $role['name'],
            'slug'       => (string) ($role['slug'] ?? ''),
            'created_at' => ! empty($role['created_at'])
                ? date('d/m/Y H:i', strtotime((string) $role['created_at']))
                : '—',
            'is_default' => $this->roleModel->isDefaultSlug((string) ($role['slug'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed>|null $role
     */
    private function respondSuccess(string $message, ?array $role = null): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'success'    => true,
            'message'    => $message,
            'role'       => $role,
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * @param array<string, mixed> $errors
     */
    private function respondError(array $errors, string $message = 'Não foi possível concluir a operação.', int $status = 422): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON([
            'success'    => false,
            'message'    => $message,
            'errors'     => $errors,
            'csrf_token' => csrf_token(),
        ]);
    }
}
