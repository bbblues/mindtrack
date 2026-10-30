<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\UnitModel;
use CodeIgniter\HTTP\ResponseInterface;

class UnitController extends BaseController
{
    protected UnitModel $unitModel;

    public function __construct()
    {
        $this->unitModel = new UnitModel();
    }

    /**
     * Lista as unidades do tenant logado.
     */
    public function index(): string
    {
        $units = $this->unitModel->orderBy('name', 'ASC')->findAll();

        return view('App\Modules\Core\Views\settings\units', [
            'title'    => 'Unidades',
            'unitRows' => array_map(fn (array $unit): array => $this->serializeUnit($unit), $units),
        ]);
    }

    /**
     * Cria uma unidade vinculada ao tenant logado.
     */
    public function store(): ResponseInterface
    {
        $data = $this->unitDataFromRequest();

        if (! $this->validateData($data, $this->unitRules(), $this->unitMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        $tenantId = $this->currentTenantId();

        if ($this->unitModel->where('name', $data['name'])->where('tenant_id', $tenantId)->first() !== null) {
            return $this->respondError(['name' => 'Já existe uma unidade com este nome na sua clínica.']);
        }

        $data['tenant_id'] = $tenantId;

        $unitId = $this->unitModel->insert($data);

        if ($unitId === false) {
            return $this->respondError($this->unitModel->errors());
        }

        $unit = $this->unitModel->find((int) $unitId);

        return $this->respondSuccess('Unidade criada com sucesso.', $unit !== null ? $this->serializeUnit($unit) : null);
    }

    /**
     * Atualiza uma unidade existente.
     */
    public function update($id = null): ResponseInterface
    {
        $unit = $this->unitModel->find((int) $id);

        if ($unit === null) {
            return $this->respondError([], 'Unidade não encontrada.', 404);
        }

        $data = $this->unitDataFromRequest();

        if (! $this->validateData($data, $this->unitRules(), $this->unitMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        $duplicate = $this->unitModel
            ->where('name', $data['name'])
            ->where('tenant_id', $this->currentTenantId())
            ->where('id !=', (int) $unit['id'])
            ->first();

        if ($duplicate !== null) {
            return $this->respondError(['name' => 'Já existe uma unidade com este nome na sua clínica.']);
        }

        if (! $this->unitModel->update((int) $unit['id'], $data)) {
            return $this->respondError($this->unitModel->errors());
        }

        $updated = $this->unitModel->find((int) $unit['id']);

        return $this->respondSuccess('Unidade atualizada com sucesso.', $updated !== null ? $this->serializeUnit($updated) : null);
    }

    /**
     * Remove a unidade (soft delete).
     */
    public function destroy($id = null): ResponseInterface
    {
        $unit = $this->unitModel->find((int) $id);

        if ($unit === null) {
            return $this->respondError([], 'Unidade não encontrada.', 404);
        }

        if (! $this->unitModel->delete((int) $unit['id'])) {
            return $this->respondError($this->unitModel->errors(), 'Não foi possível excluir a unidade.');
        }

        return $this->respondSuccess('Unidade excluída com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function unitDataFromRequest(): array
    {
        $name = trim((string) $this->request->getPost('name'));
        $cnpj = trim((string) $this->request->getPost('cnpj'));

        return [
            'name'      => $name,
            'cnpj'      => $cnpj !== '' ? $cnpj : null,
            'is_active' => (int) ($this->request->getPost('is_active') ?? 0),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function unitRules(): array
    {
        return [
            'name'      => 'required|max_length[255]',
            'cnpj'      => 'permit_empty|max_length[20]',
            'is_active' => 'permit_empty|in_list[0,1]',
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function unitMessages(): array
    {
        return [
            'name' => [
                'required'   => 'Informe o nome da unidade.',
                'max_length' => 'O nome da unidade deve ter no máximo 255 caracteres.',
            ],
            'cnpj' => [
                'max_length' => 'O CNPJ deve ter no máximo 20 caracteres.',
            ],
        ];
    }

    private function currentTenantId(): ?int
    {
        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    /**
     * @param array<string, mixed> $unit
     *
     * @return array<string, mixed>
     */
    private function serializeUnit(array $unit): array
    {
        return [
            'id'         => (int) $unit['id'],
            'name'       => (string) $unit['name'],
            'cnpj'       => (string) ($unit['cnpj'] ?? ''),
            'is_active'  => (int) ($unit['is_active'] ?? 1),
            'created_at' => ! empty($unit['created_at'])
                ? date('d/m/Y H:i', strtotime((string) $unit['created_at']))
                : '—',
        ];
    }

    /**
     * @param array<string, mixed>|null $unit
     */
    private function respondSuccess(string $message, ?array $unit = null): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'success'    => true,
            'message'    => $message,
            'unit'       => $unit,
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

