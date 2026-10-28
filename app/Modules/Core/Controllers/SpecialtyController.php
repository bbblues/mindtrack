<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\SpecialtyModel;
use CodeIgniter\HTTP\ResponseInterface;

class SpecialtyController extends BaseController
{
    protected SpecialtyModel $specialtyModel;

    public function __construct()
    {
        $this->specialtyModel = new SpecialtyModel();
    }

    /**
     * Lista as especialidades do tenant logado.
     */
    public function index(): string
    {
        $specialties = $this->specialtyModel->orderBy('name', 'ASC')->findAll();

        return view('App\Modules\Core\Views\settings\specialties', [
            'title'          => 'Especialidades',
            'specialtyRows'  => array_map(fn (array $specialty): array => $this->serializeSpecialty($specialty), $specialties),
        ]);
    }

    /**
     * Cria uma especialidade vinculada ao tenant logado.
     */
    public function store(): ResponseInterface
    {
        $name = trim((string) $this->request->getPost('name'));

        if (! $this->validateData(['name' => $name], $this->nameRules(), $this->nameMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        $tenantId = $this->currentTenantId();

        if ($this->specialtyModel->where('name', $name)->where('tenant_id', $tenantId)->first() !== null) {
            return $this->respondError(['name' => 'Já existe uma especialidade com este nome na sua clínica.']);
        }

        $specialtyId = $this->specialtyModel->insert([
            'tenant_id' => $tenantId,
            'name'      => $name,
        ]);

        if ($specialtyId === false) {
            return $this->respondError($this->specialtyModel->errors());
        }

        $specialty = $this->specialtyModel->find((int) $specialtyId);

        return $this->respondSuccess('Especialidade criada com sucesso.', $specialty !== null ? $this->serializeSpecialty($specialty) : null);
    }

    /**
     * Atualiza uma especialidade existente.
     */
    public function update($id = null): ResponseInterface
    {
        $specialty = $this->specialtyModel->find((int) $id);

        if ($specialty === null) {
            return $this->respondError([], 'Especialidade não encontrada.', 404);
        }

        $name = trim((string) $this->request->getPost('name'));

        if (! $this->validateData(['name' => $name], $this->nameRules(), $this->nameMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        $duplicate = $this->specialtyModel
            ->where('name', $name)
            ->where('tenant_id', $this->currentTenantId())
            ->where('id !=', (int) $specialty['id'])
            ->first();

        if ($duplicate !== null) {
            return $this->respondError(['name' => 'Já existe uma especialidade com este nome na sua clínica.']);
        }

        if (! $this->specialtyModel->update((int) $specialty['id'], ['name' => $name])) {
            return $this->respondError($this->specialtyModel->errors());
        }

        $updated = $this->specialtyModel->find((int) $specialty['id']);

        return $this->respondSuccess('Especialidade atualizada com sucesso.', $updated !== null ? $this->serializeSpecialty($updated) : null);
    }

    /**
     * Remove a especialidade (soft delete).
     */
    public function destroy($id = null): ResponseInterface
    {
        $specialty = $this->specialtyModel->find((int) $id);

        if ($specialty === null) {
            return $this->respondError([], 'Especialidade não encontrada.', 404);
        }

        if (! $this->specialtyModel->delete((int) $specialty['id'])) {
            return $this->respondError($this->specialtyModel->errors(), 'Não foi possível excluir a especialidade.');
        }

        return $this->respondSuccess('Especialidade excluída com sucesso.');
    }

    /**
     * @return array<string, string>
     */
    private function nameRules(): array
    {
        return ['name' => 'required|max_length[255]'];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function nameMessages(): array
    {
        return [
            'name' => [
                'required'   => 'Informe o nome da especialidade.',
                'max_length' => 'O nome da especialidade deve ter no máximo 255 caracteres.',
            ],
        ];
    }

    private function currentTenantId(): ?int
    {
        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    /**
     * @param array<string, mixed> $specialty
     *
     * @return array<string, mixed>
     */
    private function serializeSpecialty(array $specialty): array
    {
        return [
            'id'         => (int) $specialty['id'],
            'name'       => (string) $specialty['name'],
            'created_at' => ! empty($specialty['created_at'])
                ? date('d/m/Y H:i', strtotime((string) $specialty['created_at']))
                : '—',
        ];
    }

    /**
     * @param array<string, mixed>|null $specialty
     */
    private function respondSuccess(string $message, ?array $specialty = null): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'success'    => true,
            'message'    => $message,
            'specialty'  => $specialty,
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
