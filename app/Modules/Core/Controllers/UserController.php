<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\RoleModel;
use App\Modules\Core\Models\RoleUserModel;
use App\Modules\Core\Models\TenantModel;
use App\Modules\Core\Models\UnitModel;
use App\Modules\Core\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class UserController extends BaseController
{
    protected UserModel $userModel;

    protected RoleModel $roleModel;

    protected RoleUserModel $roleUserModel;

    protected UnitModel $unitModel;

    protected TenantModel $tenantModel;

    public function __construct()
    {
        $this->userModel     = new UserModel();
        $this->roleModel     = new RoleModel();
        $this->roleUserModel = new RoleUserModel();
        $this->unitModel     = new UnitModel();
        $this->tenantModel   = new TenantModel();
    }

    /**
     * Lista os usuarios do tenant com busca e paginacao.
     */
    public function index(): string
    {
        $search = trim((string) $this->request->getGet('search'));

        $users = $this->userModel->paginateWithUnit($search);

        $rolesByUser = $this->userModel->rolesForUsers(
            array_map('intval', array_column($users, 'id'))
        );

        return view('App\Modules\Core\Views\users\index', [
            'title'       => 'Usuários',
            'basePath'    => $this->basePath(),
            'users'       => $users,
            'rolesByUser' => $rolesByUser,
            'pager'       => $this->userModel->pager,
            'search'      => $search,
        ]);
    }

    /**
     * Exibe o formulario de criacao.
     */
    public function create(): string
    {
        return view('App\Modules\Core\Views\users\form', $this->formData(null));
    }

    /**
     * Cadastra um novo usuario (com hash de senha) e sincroniza os cargos.
     */
    public function store(): RedirectResponse
    {
        $data = $this->userDataFromRequest();

        $rules             = $this->userModel->getValidationRules();
        $rules['password'] = 'required|min_length[8]';

        if (! $this->validateData($data, $rules, $this->userModel->getValidationMessages())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($this->userModel->emailExists((string) $data['email'])) {
            return redirect()->back()->withInput()->with('errors', ['email' => 'Este e-mail já está em uso.']);
        }

        $data['tenant_id']      = $this->resolveTenantId();
        $data['is_super_admin'] = $this->resolveIsSuperAdmin();

        $userId = $this->userModel->insert($data);

        if ($userId === false) {
            return redirect()->back()->withInput()->with('errors', $this->userModel->errors());
        }

        $this->roleUserModel->syncRoles((int) $userId, (array) ($this->request->getPost('roles') ?? []));

        return redirect()->to(site_url($this->basePath()))->with('success', 'Usuário criado com sucesso.');
    }

    /**
     * Exibe o formulario de edicao com os cargos ja marcados.
     */
    public function edit($id = null): string|RedirectResponse
    {
        $user = $this->userModel->find((int) $id);

        if ($user === null) {
            return redirect()->to(site_url($this->basePath()))->with('error', 'Usuário não encontrado.');
        }

        return view('App\Modules\Core\Views\users\form', $this->formData($user));
    }

    /**
     * Atualiza os dados do usuario e sincroniza os cargos.
     */
    public function update($id = null): RedirectResponse
    {
        $user = $this->userModel->find((int) $id);

        if ($user === null) {
            return redirect()->to(site_url($this->basePath()))->with('error', 'Usuário não encontrado.');
        }

        $data = $this->userDataFromRequest(true);

        $rules = $this->userModel->getValidationRules();

        if (! $this->validateData($data, $rules, $this->userModel->getValidationMessages())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($this->userModel->emailExists((string) $data['email'], (int) $id)) {
            return redirect()->back()->withInput()->with('errors', ['email' => 'Este e-mail já está em uso.']);
        }

        $data['tenant_id']      = $this->resolveTenantId();
        $data['is_super_admin'] = $this->resolveIsSuperAdmin();

        if (! $this->userModel->update((int) $id, $data)) {
            return redirect()->back()->withInput()->with('errors', $this->userModel->errors());
        }

        $this->roleUserModel->syncRoles((int) $id, (array) ($this->request->getPost('roles') ?? []));

        return redirect()->to(site_url($this->basePath()))->with('success', 'Usuário atualizado com sucesso.');
    }

    /**
     * Dados comuns para o formulario de criacao/edicao.
     *
     * @return array<string, mixed>
     */
    private function formData(?array $user): array
    {
        $isSuperAdmin = (int) session('is_super_admin') === 1;

        $selectedRoleIds = $user !== null
            ? $this->roleUserModel->roleIdsForUser((int) $user['id'])
            : array_map('intval', (array) (old('roles') ?? []));

        return [
            'title'           => $user === null ? 'Novo Usuário' : 'Editar Usuário',
            'basePath'        => $this->basePath(),
            'user'            => $user,
            'units'           => $this->unitModel->orderBy('name', 'ASC')->findAll(),
            'roles'           => $this->roleModel->rolesForTenant($this->roleTenantIdForForm($user)),
            'tenants'         => $isSuperAdmin ? $this->tenantModel->orderBy('name', 'ASC')->findAll() : [],
            'selectedRoleIds' => $selectedRoleIds,
            'isSuperAdmin'    => $isSuperAdmin,
        ];
    }

    /**
     * Normaliza os dados do usuario vindos do formulario.
     *
     * @return array<string, mixed>
     */
    private function userDataFromRequest(bool $isUpdate = false): array
    {
        $data = $this->request->getPost(['name', 'email', 'password', 'unit_id', 'is_active']);

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value       = trim($value);
                $data[$key] = $value === '' ? null : $value;
            }
        }

        // Na edicao, senha em branco mantem a atual.
        if ($isUpdate && ($data['password'] ?? null) === null) {
            unset($data['password']);
        }

        $data['is_active'] = (int) ($data['is_active'] ?? 1);
        $data['unit_id']   = ($data['unit_id'] ?? null) !== null ? (int) $data['unit_id'] : null;

        return $data;
    }

    /**
     * Base das URLs do modulo na requisicao atual.
     *
     * Permite o mesmo controller atender tanto /users (atalho da sidebar)
     * quanto /settings/users (card dentro do hub de configuracoes), mantendo
     * links, redirects e actions no contexto em que o usuario esta navegando.
     */
    private function basePath(): string
    {
        $path = trim($this->request->getPath(), '/');

        return str_starts_with($path, 'settings/users') ? 'settings/users' : 'users';
    }

    /**
     * Tenant do usuario logado (ou selecionado pelo Super Admin).
     */
    private function resolveTenantId(): ?int
    {
        if ((int) session('is_super_admin') === 1) {
            $tenantId = $this->request->getPost('tenant_id');

            return is_numeric($tenantId) && (int) $tenantId > 0 ? (int) $tenantId : null;
        }

        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    /**
     * Tenant usado para listar os cargos disponiveis no formulario.
     * Na edicao, usa o tenant do proprio usuario. Na criacao, usa o
     * tenant da sessao (ou o selecionado pelo Super Admin).
     */
    private function roleTenantIdForForm(?array $user): ?int
    {
        if ($user !== null) {
            return is_numeric($user['tenant_id'] ?? null) ? (int) $user['tenant_id'] : null;
        }

        if ((int) session('is_super_admin') === 1) {
            $tenantId = old('tenant_id');

            return is_numeric($tenantId) && (int) $tenantId > 0 ? (int) $tenantId : null;
        }

        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    /**
     * Apenas o Super Admin pode elevar um usuario a Super Admin.
     */
    private function resolveIsSuperAdmin(): int
    {
        if ((int) session('is_super_admin') !== 1) {
            return 0;
        }

        return (int) ($this->request->getPost('is_super_admin') ?? 0);
    }
}
