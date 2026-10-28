<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\ModuleModel;
use App\Modules\Core\Models\ModuleTenantModel;
use App\Modules\Core\Models\TenantModel;
use CodeIgniter\HTTP\RedirectResponse;

class TenantController extends BaseController
{
    protected TenantModel $tenantModel;

    protected ModuleModel $moduleModel;

    protected ModuleTenantModel $moduleTenantModel;

    public function __construct()
    {
        $this->tenantModel       = new TenantModel();
        $this->moduleModel       = new ModuleModel();
        $this->moduleTenantModel = new ModuleTenantModel();
    }

    /**
     * Lista todas as clinicas (tenants) com os modulos ativos.
     */
    public function index(): string
    {
        return view('App\Modules\Core\Views\tenants\index', [
            'title'           => 'Gestão SaaS',
            'tenants'         => $this->tenantModel->orderBy('name', 'ASC')->findAll(),
            'modulesByTenant' => $this->modulesByTenant(),
        ]);
    }

    /**
     * Exibe o formulario de criacao com os modulos base ja marcados.
     */
    public function create(): string
    {
        return view('App\Modules\Core\Views\tenants\form', [
            'title'           => 'Nova Clínica',
            'tenant'          => null,
            'modules'         => $this->moduleModel->orderBy('name', 'ASC')->findAll(),
            'selectedModules' => $this->baseModuleIds(),
        ]);
    }

    /**
     * Cadastra uma nova clinica e vincula os modulos (base + extras).
     */
    public function store(): RedirectResponse
    {
        $data = $this->tenantDataFromRequest();

        if (! $this->validateData($data, $this->tenantModel->getValidationRules(), $this->tenantModel->getValidationMessages())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $selectedModules = $this->request->getPost('modules') ?? [];

        $tenantId = $this->tenantModel->createTenantWithBaseModules($data, $selectedModules);

        if ($tenantId === false) {
            return redirect()->back()->withInput()->with('errors', $this->tenantModel->errors());
        }

        return redirect()->to(site_url('tenants'))->with('success', 'Clínica criada com sucesso.');
    }

    /**
     * Exibe o formulario de edicao com os modulos ja marcados.
     */
    public function edit($id = null): string|RedirectResponse
    {
        $tenant = $this->tenantModel->find((int) $id);

        if ($tenant === null) {
            return redirect()->to(site_url('tenants'))->with('error', 'Clínica não encontrada.');
        }

        return view('App\Modules\Core\Views\tenants\form', [
            'title'           => 'Editar Clínica',
            'tenant'          => $tenant,
            'modules'         => $this->moduleModel->orderBy('name', 'ASC')->findAll(),
            'selectedModules' => $this->modulesForTenant((int) $id),
        ]);
    }

    /**
     * Atualiza os dados da clinica e sincroniza os modulos ativos.
     */
    public function update($id = null): RedirectResponse
    {
        $tenant = $this->tenantModel->find((int) $id);

        if ($tenant === null) {
            return redirect()->to(site_url('tenants'))->with('error', 'Clínica não encontrada.');
        }

        $data       = $this->tenantDataFromRequest();
        $data['id'] = (int) $id; // o is_unique usa {id} para ignorar o proprio registro

        if (! $this->validateData($data, $this->tenantModel->getValidationRules(), $this->tenantModel->getValidationMessages())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        unset($data['id']);

        if (! $this->tenantModel->update((int) $id, $data)) {
            return redirect()->back()->withInput()->with('errors', $this->tenantModel->errors());
        }

        $this->tenantModel->syncModules((int) $id, $this->request->getPost('modules') ?? []);

        return redirect()->to(site_url('tenants'))->with('success', 'Clínica atualizada com sucesso.');
    }

    /**
     * Normaliza os dados da clinica vindos do formulario.
     *
     * @return array<string, mixed>
     */
    private function tenantDataFromRequest(): array
    {
        $data = $this->request->getPost(['name', 'slug', 'cnpj', 'email', 'phone', 'is_active']);

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value       = trim($value);
                $data[$key] = $value === '' ? null : $value;
            }
        }

        $data['is_active'] = (int) ($data['is_active'] ?? 1);

        return $data;
    }

    /**
     * Ids dos modulos base (obrigatorios para toda clinica).
     *
     * @return list<int>
     */
    private function baseModuleIds(): array
    {
        return array_map('intval', array_column(
            $this->moduleModel->whereIn('slug', ['core', 'patients', 'agenda'])->findAll(),
            'id'
        ));
    }

    /**
     * Ids dos modulos ativos de um tenant (sempre inclui os base).
     *
     * @return list<int>
     */
    private function modulesForTenant(int $tenantId): array
    {
        $rows   = $this->moduleTenantModel->where('tenant_id', $tenantId)->findAll();
        $active = array_map('intval', array_column($rows, 'module_id'));

        return array_values(array_unique(array_merge($this->baseModuleIds(), $active)));
    }

    /**
     * Mapa [tenant_id => lista de modulos] para exibicao na listagem.
     *
     * @return array<int, list<array<string, mixed>>>
     */
    private function modulesByTenant(): array
    {
        $modules   = $this->moduleModel->orderBy('name', 'ASC')->findAll();
        $moduleMap = [];
        foreach ($modules as $module) {
            $moduleMap[(int) $module['id']] = $module;
        }

        $byTenant = [];
        foreach ($this->moduleTenantModel->findAll() as $link) {
            $tenantId = (int) $link['tenant_id'];
            $moduleId = (int) $link['module_id'];

            if (isset($moduleMap[$moduleId])) {
                $byTenant[$tenantId][] = $moduleMap[$moduleId];
            }
        }

        return $byTenant;
    }
}
