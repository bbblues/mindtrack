<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\RoomModel;
use App\Modules\Core\Models\UnitModel;
use CodeIgniter\HTTP\ResponseInterface;

class RoomController extends BaseController
{
    protected RoomModel $roomModel;
    protected UnitModel $unitModel;

    public function __construct()
    {
        $this->roomModel = new RoomModel();
        $this->unitModel = new UnitModel();
    }

    /**
     * Lista as salas do tenant logado e as unidades disponíveis.
     */
    public function index(): string
    {
        $rooms = $this->roomModel->orderBy('name', 'ASC')->findAll();
        $units = $this->unitModel->orderBy('name', 'ASC')->findAll();

        $unitNames   = [];
        $unitOptions = [];

        foreach ($units as $unit) {
            $id             = (int) $unit['id'];
            $unitNames[$id] = (string) $unit['name'];
            $unitOptions[]  = ['id' => $id, 'name' => (string) $unit['name']];
        }

        return view('App\Modules\Core\Views\settings\rooms', [
            'title'       => 'Salas',
            'roomRows'    => array_map(fn (array $room): array => $this->serializeRoom($room, $unitNames), $rooms),
            'unitOptions' => $unitOptions,
        ]);
    }

    /**
     * Cria uma sala vinculada ao tenant logado.
     */
    public function store(): ResponseInterface
    {
        $data = $this->roomDataFromRequest();

        if (! $this->validateData($data, $this->roomRules(), $this->roomMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        if ($this->unitModel->find((int) $data['unit_id']) === null) {
            return $this->respondError(['unit_id' => 'Unidade não encontrada na sua clínica.']);
        }

        $tenantId = $this->currentTenantId();

        if ($this->roomModel
            ->where('name', $data['name'])
            ->where('unit_id', (int) $data['unit_id'])
            ->where('tenant_id', $tenantId)
            ->first() !== null) {
            return $this->respondError(['name' => 'Já existe uma sala com este nome nesta unidade.']);
        }

        $data['tenant_id'] = $tenantId;
        $data['unit_id']   = (int) $data['unit_id'];

        $roomId = $this->roomModel->insert($data);

        if ($roomId === false) {
            return $this->respondError($this->roomModel->errors());
        }

        $room = $this->roomModel->find((int) $roomId);

        return $this->respondSuccess('Sala criada com sucesso.', $room !== null ? $this->serializeRoom($room) : null);
    }

    /**
     * Atualiza uma sala existente.
     */
    public function update($id = null): ResponseInterface
    {
        $room = $this->roomModel->find((int) $id);

        if ($room === null) {
            return $this->respondError([], 'Sala não encontrada.', 404);
        }

        $data = $this->roomDataFromRequest();

        if (! $this->validateData($data, $this->roomRules(), $this->roomMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        if ($this->unitModel->find((int) $data['unit_id']) === null) {
            return $this->respondError(['unit_id' => 'Unidade não encontrada na sua clínica.']);
        }

        $duplicate = $this->roomModel
            ->where('name', $data['name'])
            ->where('unit_id', (int) $data['unit_id'])
            ->where('tenant_id', $this->currentTenantId())
            ->where('id !=', (int) $room['id'])
            ->first();

        if ($duplicate !== null) {
            return $this->respondError(['name' => 'Já existe uma sala com este nome nesta unidade.']);
        }

        if (! $this->roomModel->update((int) $room['id'], $data)) {
            return $this->respondError($this->roomModel->errors());
        }

        $updated = $this->roomModel->find((int) $room['id']);

        return $this->respondSuccess('Sala atualizada com sucesso.', $updated !== null ? $this->serializeRoom($updated) : null);
    }

    /**
     * Remove a sala (soft delete).
     */
    public function destroy($id = null): ResponseInterface
    {
        $room = $this->roomModel->find((int) $id);

        if ($room === null) {
            return $this->respondError([], 'Sala não encontrada.', 404);
        }

        if (! $this->roomModel->delete((int) $room['id'])) {
            return $this->respondError($this->roomModel->errors(), 'Não foi possível excluir a sala.');
        }

        return $this->respondSuccess('Sala excluída com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function roomDataFromRequest(): array
    {
        return [
            'name'    => trim((string) $this->request->getPost('name')),
            'unit_id' => (int) $this->request->getPost('unit_id'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function roomRules(): array
    {
        return [
            'name'    => 'required|max_length[255]',
            'unit_id' => 'required|is_natural_no_zero',
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function roomMessages(): array
    {
        return [
            'name' => [
                'required'   => 'Informe o nome da sala.',
                'max_length' => 'O nome da sala deve ter no máximo 255 caracteres.',
            ],
            'unit_id' => [
                'required'           => 'Selecione a unidade da sala.',
                'is_natural_no_zero' => 'Selecione uma unidade válida.',
            ],
        ];
    }

    private function currentTenantId(): ?int
    {
        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    /**
     * @return array<int, string>
     */
    private function unitNameMap(): array
    {
        $map = [];

        foreach ($this->unitModel->orderBy('name', 'ASC')->findAll() as $unit) {
            $map[(int) $unit['id']] = (string) $unit['name'];
        }

        return $map;
    }

    /**
     * @param array<string, mixed>    $room
     * @param array<int, string>|null $unitNames
     *
     * @return array<string, mixed>
     */
    private function serializeRoom(array $room, ?array $unitNames = null): array
    {
        $unitNames ??= $this->unitNameMap();
        $unitId       = (int) $room['unit_id'];

        return [
            'id'        => (int) $room['id'],
            'name'      => (string) $room['name'],
            'unit_id'   => $unitId,
            'unit_name' => $unitNames[$unitId] ?? '—',
        ];
    }

    /**
     * @param array<string, mixed>|null $room
     */
    private function respondSuccess(string $message, ?array $room = null): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'success'    => true,
            'message'    => $message,
            'room'       => $room,
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
