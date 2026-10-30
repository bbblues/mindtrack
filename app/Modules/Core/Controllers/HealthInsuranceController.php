<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\HealthInsuranceModel;
use CodeIgniter\HTTP\ResponseInterface;

class HealthInsuranceController extends BaseController
{
    protected HealthInsuranceModel $insuranceModel;

    public function __construct()
    {
        $this->insuranceModel = new HealthInsuranceModel();
    }

    /**
     * Lista os convenios do tenant logado.
     */
    public function index(): string
    {
        $insurances = $this->insuranceModel->orderBy('name', 'ASC')->findAll();

        return view('App\Modules\Core\Views\settings\insurances', [
            'title'         => 'Convênios',
            'insuranceRows' => array_map(fn (array $insurance): array => $this->serializeInsurance($insurance), $insurances),
        ]);
    }

    /**
     * Cria um convenio vinculado ao tenant logado.
     */
    public function store(): ResponseInterface
    {
        $data = $this->insuranceDataFromRequest();

        if (! $this->validateData($data, $this->insuranceRules(), $this->insuranceMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        $tenantId = $this->currentTenantId();

        if ($this->insuranceModel->where('name', $data['name'])->where('tenant_id', $tenantId)->first() !== null) {
            return $this->respondError(['name' => 'Já existe um convênio com este nome na sua clínica.']);
        }

        $data['tenant_id'] = $tenantId;

        $insuranceId = $this->insuranceModel->insert($data);

        if ($insuranceId === false) {
            return $this->respondError($this->insuranceModel->errors());
        }

        $insurance = $this->insuranceModel->find((int) $insuranceId);

        return $this->respondSuccess('Convênio criado com sucesso.', $insurance !== null ? $this->serializeInsurance($insurance) : null);
    }

    /**
     * Atualiza um convenio existente.
     */
    public function update($id = null): ResponseInterface
    {
        $insurance = $this->insuranceModel->find((int) $id);

        if ($insurance === null) {
            return $this->respondError([], 'Convênio não encontrado.', 404);
        }

        $data = $this->insuranceDataFromRequest();

        if (! $this->validateData($data, $this->insuranceRules(), $this->insuranceMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        $duplicate = $this->insuranceModel
            ->where('name', $data['name'])
            ->where('tenant_id', $this->currentTenantId())
            ->where('id !=', (int) $insurance['id'])
            ->first();

        if ($duplicate !== null) {
            return $this->respondError(['name' => 'Já existe um convênio com este nome na sua clínica.']);
        }

        if (! $this->insuranceModel->update((int) $insurance['id'], $data)) {
            return $this->respondError($this->insuranceModel->errors());
        }

        $updated = $this->insuranceModel->find((int) $insurance['id']);

        return $this->respondSuccess('Convênio atualizado com sucesso.', $updated !== null ? $this->serializeInsurance($updated) : null);
    }

    /**
     * Remove o convenio (soft delete).
     */
    public function destroy($id = null): ResponseInterface
    {
        $insurance = $this->insuranceModel->find((int) $id);

        if ($insurance === null) {
            return $this->respondError([], 'Convênio não encontrado.', 404);
        }

        if (! $this->insuranceModel->delete((int) $insurance['id'])) {
            return $this->respondError($this->insuranceModel->errors(), 'Não foi possível excluir o convênio.');
        }

        return $this->respondSuccess('Convênio excluído com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function insuranceDataFromRequest(): array
    {
        $name        = trim((string) $this->request->getPost('name'));
        $ansRegistry = trim((string) $this->request->getPost('ans_registry'));

        return [
            'name'          => $name,
            'ans_registry'  => $ansRegistry !== '' ? $ansRegistry : null,
            'requires_card' => (int) ($this->request->getPost('requires_card') ?? 0),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function insuranceRules(): array
    {
        return [
            'name'          => 'required|max_length[255]',
            'ans_registry'  => 'permit_empty|max_length[20]',
            'requires_card' => 'permit_empty|in_list[0,1]',
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function insuranceMessages(): array
    {
        return [
            'name' => [
                'required'   => 'Informe o nome do convênio.',
                'max_length' => 'O nome do convênio deve ter no máximo 255 caracteres.',
            ],
            'ans_registry' => [
                'max_length' => 'O registro ANS deve ter no máximo 20 caracteres.',
            ],
        ];
    }

    private function currentTenantId(): ?int
    {
        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    /**
     * @param array<string, mixed> $insurance
     *
     * @return array<string, mixed>
     */
    private function serializeInsurance(array $insurance): array
    {
        return [
            'id'            => (int) $insurance['id'],
            'name'          => (string) $insurance['name'],
            'ans_registry'  => (string) ($insurance['ans_registry'] ?? ''),
            'requires_card' => (int) ($insurance['requires_card'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed>|null $insurance
     */
    private function respondSuccess(string $message, ?array $insurance = null): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'success'    => true,
            'message'    => $message,
            'insurance'  => $insurance,
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

