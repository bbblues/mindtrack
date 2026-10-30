<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Models\BaseModel;
use App\Modules\Core\Models\AbsenceReasonModel;
use App\Modules\Core\Models\DischargeReasonModel;
use CodeIgniter\HTTP\ResponseInterface;

class ReasonController extends BaseController
{
    protected AbsenceReasonModel $absenceModel;
    protected DischargeReasonModel $dischargeModel;

    public function __construct()
    {
        $this->absenceModel   = new AbsenceReasonModel();
        $this->dischargeModel = new DischargeReasonModel();
    }

    /**
     * Lista os motivos de falta e de alta do tenant logado.
     */
    public function index(): string
    {
        return view('App\Modules\Core\Views\settings\reasons', [
            'title'         => 'Motivos de Falta e Alta',
            'absenceRows'   => array_map(fn (array $reason): array => $this->serializeReason($reason), $this->absenceModel->orderBy('name', 'ASC')->findAll()),
            'dischargeRows' => array_map(fn (array $reason): array => $this->serializeReason($reason), $this->dischargeModel->orderBy('name', 'ASC')->findAll()),
        ]);
    }

    public function storeAbsence(): ResponseInterface
    {
        return $this->storeReason('absence');
    }

    public function storeDischarge(): ResponseInterface
    {
        return $this->storeReason('discharge');
    }

    public function updateAbsence($id = null): ResponseInterface
    {
        return $this->updateReason('absence', $id);
    }

    public function updateDischarge($id = null): ResponseInterface
    {
        return $this->updateReason('discharge', $id);
    }

    public function destroyAbsence($id = null): ResponseInterface
    {
        return $this->destroyReason('absence', $id);
    }

    public function destroyDischarge($id = null): ResponseInterface
    {
        return $this->destroyReason('discharge', $id);
    }

    private function modelFor(string $type): BaseModel
    {
        return $type === 'absence' ? $this->absenceModel : $this->dischargeModel;
    }

    private function label(string $type): string
    {
        return $type === 'absence' ? 'motivo de falta' : 'motivo de alta';
    }

    private function storeReason(string $type): ResponseInterface
    {
        $model = $this->modelFor($type);
        $name  = trim((string) $this->request->getPost('name'));

        if (! $this->validateData(['name' => $name], $this->reasonRules(), $this->reasonMessages($type))) {
            return $this->respondError($this->validator->getErrors());
        }

        $tenantId = $this->currentTenantId();

        if ($model->where('name', $name)->where('tenant_id', $tenantId)->first() !== null) {
            return $this->respondError(['name' => 'Já existe um ' . $this->label($type) . ' com este nome na sua clínica.']);
        }

        $reasonId = $model->insert([
            'tenant_id' => $tenantId,
            'name'      => $name,
        ]);

        if ($reasonId === false) {
            return $this->respondError($model->errors());
        }

        $reason = $model->find((int) $reasonId);

        return $this->respondSuccess(ucfirst($this->label($type)) . ' criado com sucesso.', $reason !== null ? $this->serializeReason($reason) : null);
    }

    private function updateReason(string $type, $id = null): ResponseInterface
    {
        $model  = $this->modelFor($type);
        $reason = $model->find((int) $id);

        if ($reason === null) {
            return $this->respondError([], ucfirst($this->label($type)) . ' não encontrado.', 404);
        }

        $name = trim((string) $this->request->getPost('name'));

        if (! $this->validateData(['name' => $name], $this->reasonRules(), $this->reasonMessages($type))) {
            return $this->respondError($this->validator->getErrors());
        }

        $duplicate = $model
            ->where('name', $name)
            ->where('tenant_id', $this->currentTenantId())
            ->where('id !=', (int) $reason['id'])
            ->first();

        if ($duplicate !== null) {
            return $this->respondError(['name' => 'Já existe um ' . $this->label($type) . ' com este nome na sua clínica.']);
        }

        if (! $model->update((int) $reason['id'], ['name' => $name])) {
            return $this->respondError($model->errors());
        }

        $updated = $model->find((int) $reason['id']);

        return $this->respondSuccess(ucfirst($this->label($type)) . ' atualizado com sucesso.', $updated !== null ? $this->serializeReason($updated) : null);
    }

    private function destroyReason(string $type, $id = null): ResponseInterface
    {
        $model  = $this->modelFor($type);
        $reason = $model->find((int) $id);

        if ($reason === null) {
            return $this->respondError([], ucfirst($this->label($type)) . ' não encontrado.', 404);
        }

        if (! $model->delete((int) $reason['id'])) {
            return $this->respondError($model->errors(), 'Não foi possível excluir o ' . $this->label($type) . '.');
        }

        return $this->respondSuccess(ucfirst($this->label($type)) . ' excluído com sucesso.');
    }

    /**
     * @return array<string, string>
     */
    private function reasonRules(): array
    {
        return ['name' => 'required|max_length[255]'];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function reasonMessages(string $type): array
    {
        $label = $this->label($type);

        return [
            'name' => [
                'required'   => 'Informe o nome do ' . $label . '.',
                'max_length' => 'O nome do ' . $label . ' deve ter no máximo 255 caracteres.',
            ],
        ];
    }

    private function currentTenantId(): ?int
    {
        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    /**
     * @param array<string, mixed> $reason
     *
     * @return array<string, mixed>
     */
    private function serializeReason(array $reason): array
    {
        return [
            'id'   => (int) $reason['id'],
            'name' => (string) $reason['name'],
        ];
    }

    /**
     * @param array<string, mixed>|null $reason
     */
    private function respondSuccess(string $message, ?array $reason = null): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'success'    => true,
            'message'    => $message,
            'reason'     => $reason,
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
