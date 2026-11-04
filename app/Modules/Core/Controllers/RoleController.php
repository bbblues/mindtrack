<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Libraries\PermissionCatalog;
use App\Modules\Core\Models\PermissionModel;
use App\Modules\Core\Models\PermissionRoleModel;
use App\Modules\Core\Models\RoleModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class RoleController extends BaseController
{
    protected RoleModel $roleModel;

    private const MODULE_LABELS = [
        'patients'    => 'Pacientes',
        'agenda'      => 'Agenda',
        'assessments' => 'Avaliações',
        'core'        => 'Configurações',
    ];

    private const ACTION_LABELS = [
        'patients.create'           => 'Criar paciente',
        'patients.view'             => 'Visualizar paciente',
        'patients.edit'             => 'Editar paciente',
        'patients.deactivate'       => 'Desativar paciente',
        'agenda.create'             => 'Criar agendamento',
        'agenda.view'               => 'Visualizar agenda',
        'agenda.edit'               => 'Editar agendamento',
        'agenda.confirm'            => 'Confirmar / marcar falta',
        'assessments.start'         => 'Iniciar avaliação',
        'assessments.edit'          => 'Editar avaliações',
        'assessments.finalize'      => 'Finalizar avaliação',
        'assessments.export_pdf'    => 'Exportar avaliação em PDF',
        'settings.manage_catalog'   => 'Gerenciar Unidades, Salas, Especialidades e Convênios',
        'settings.manage_users'     => 'Gerenciar Usuários',
        'settings.manage_roles'     => 'Gerenciar Cargos e Permissões',
        'dashboard.view_management' => 'Visualizar painel de gestão',
    ];

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
            'basePath' => $this->basePath(),
            'roleRows' => $roleRows,
        ]);
    }

    /**
     * Exibe a tela de gerenciamento de permissoes de um cargo.
     */
    public function permissions($id = null): string|RedirectResponse
    {
        $role = $this->roleModel->find((int) $id);

        if ($role === null) {
            return redirect()->to(site_url($this->basePath()))->with('error', 'Cargo não encontrado.');
        }

        $roles = $this->roleModel->orderBy('name', 'ASC')->findAll();

        $roleRows = [];
        foreach ($roles as $r) {
            $roleRows[] = [
                'id'         => (int) $r['id'],
                'name'       => (string) $r['name'],
                'slug'       => (string) ($r['slug'] ?? ''),
                'is_default' => $this->roleModel->isDefaultSlug((string) ($r['slug'] ?? '')),
            ];
        }

        $permissionRoleModel = new PermissionRoleModel();

        $rolePermissionIds = [];
        foreach ($roles as $r) {
            $rolePermissionIds[(int) $r['id']] = $permissionRoleModel->permissionIdsForRole((int) $r['id']);
        }

        return view('App\Modules\Core\Views\roles\permissions', [
            'title'             => 'Cargos e Permissões',
            'basePath'          => $this->basePath(),
            'roles'             => $roleRows,
            'modules'           => $this->permissionModules(),
            'rolePermissionIds' => $rolePermissionIds,
            'selectedRoleId'    => (int) $role['id'],
        ]);
    }

    /**
     * Sincroniza as permissoes selecionadas de um cargo (via AJAX).
     */
    public function updatePermissions($id = null): ResponseInterface
    {
        $role = $this->roleModel->find((int) $id);

        if ($role === null) {
            return $this->respondError([], 'Cargo não encontrado.', 404);
        }

        if ((string) ($role['slug'] ?? '') === 'admin') {
            return $this->respondError([], 'O cargo Admin possui acesso total e não pode ser editado.', 422);
        }

        $permissionIds = $this->request->getPost('permission_ids');
        if (! is_array($permissionIds)) {
            $permissionIds = [];
        }

        $permissionIds = array_values(array_unique(array_filter(array_map('intval', $permissionIds), static fn (int $v): bool => $v > 0)));

        (new PermissionRoleModel())->syncPermissions((int) $role['id'], $permissionIds);

        return $this->respondSuccess('Permissões salvas com sucesso.');
    }

    /**
     * Catalogo de permissoes agrupado por modulo e acao (para a tela).
     *
     * @return array<int, array<string, mixed>>
     */
    private function permissionModules(): array
    {
        $permissionModel = new PermissionModel();
        $permissionMap   = [];

        foreach ($permissionModel->findAll() as $permission) {
            $permissionMap[(string) $permission['slug']] = (int) $permission['id'];
        }

        $singles = [];
        $scoped  = [];

        foreach (PermissionCatalog::all() as $item) {
            $module       = (string) ($item['module'] ?? '');
            $slug         = (string) ($item['slug'] ?? '');
            $scope        = isset($item['scope']) ? (string) $item['scope'] : null;
            $sort         = (int) ($item['sort_order'] ?? 0);
            $permissionId = $permissionMap[$slug] ?? 0;

            if ($module === '') {
                continue;
            }

            if ($scope === null) {
                $singles[$module][] = [
                    'type'         => 'single',
                    'label'        => $this->actionLabel($slug),
                    'sort'         => $sort,
                    'permissionId' => $permissionId,
                    'slug'         => $slug,
                ];
                continue;
            }

            $base = (string) preg_replace('/\.(any|own)$/', '', $slug);

            if (! isset($scoped[$module][$base])) {
                $scoped[$module][$base] = [
                    'type'  => 'scoped',
                    'base'  => $base,
                    'label' => $this->actionLabel($base),
                    'sort'  => $sort,
                    'any'   => null,
                    'own'   => null,
                ];
            }

            $scoped[$module][$base][$scope] = [
                'permissionId' => $permissionId,
                'slug'         => $slug,
            ];
        }

        $modules = [];

        foreach (self::MODULE_LABELS as $moduleSlug => $moduleLabel) {
            $actions = array_merge($singles[$moduleSlug] ?? [], array_values($scoped[$moduleSlug] ?? []));

            usort($actions, static fn (array $a, array $b): int => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

            $modules[] = [
                'slug'    => $moduleSlug,
                'label'   => $moduleLabel,
                'actions' => $actions,
            ];
        }

        return $modules;
    }

    private function actionLabel(string $slug): string
    {
        return self::ACTION_LABELS[$slug] ?? $slug;
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

    /**
     * Base das URLs do modulo na requisicao atual.
     *
     * Mantem o CRUD de cargos funcionando tanto em /roles quanto em
     * /settings/roles (card dentro do hub de configuracoes).
     */
    private function basePath(): string
    {
        $path = trim($this->request->getPath(), '/');

        return str_starts_with($path, 'settings/roles') ? 'settings/roles' : 'roles';
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
            'csrf_token' => csrf_hash(),
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
            'csrf_token' => csrf_hash(),
        ]);
    }
}
