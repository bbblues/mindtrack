<?php

namespace App\Modules\Agenda\Controllers;

use App\Controllers\BaseController;
use App\Modules\Agenda\Models\ProfessionalScheduleModel;
use App\Modules\Core\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class ProfessionalScheduleController extends BaseController
{
    protected ProfessionalScheduleModel $scheduleModel;

    protected UserModel $userModel;

    public function __construct()
    {
        $this->scheduleModel = new ProfessionalScheduleModel();
        $this->userModel     = new UserModel();
    }

    /**
     * Tela de configuração da disponibilidade semanal dos profissionais.
     */
    public function index(): string
    {
        $professionals = $this->userModel->evaluators();

        $schedulesByProfessional = [];
        foreach ($professionals as $professional) {
            $id = (int) $professional['id'];
            $schedulesByProfessional[$id] = $this->scheduleModel->groupedForProfessional($id);
        }

        return view('App\Modules\Agenda\Views\settings\professional_schedules', [
            'title'                   => 'Disponibilidade dos Profissionais',
            'professionals'           => $professionals,
            'schedulesByProfessional' => $schedulesByProfessional,
        ]);
    }

    /**
     * Salva (substitui) a agenda semanal de um profissional.
     */
    public function store(): ResponseInterface
    {
        $payload        = $this->requestPayload();
        $professionalId = (int) ($payload['professional_id'] ?? 0);

        if ($professionalId <= 0) {
            return $this->respondError([], 'Selecione um profissional válido.');
        }

        $professionalIds = array_map('intval', array_column($this->userModel->evaluators(), 'id'));

        if (! in_array($professionalId, $professionalIds, true)) {
            return $this->respondError([], 'O usuário informado não é um profissional válido.');
        }

        $slots = $this->normalizeSlots($payload['days'] ?? null);

        if ($slots === null) {
            return $this->respondError([], 'Informe horários válidos para a agenda.');
        }

        $tenantId = $this->currentTenantId();

        if (! $this->scheduleModel->replaceForProfessional($professionalId, $tenantId, $slots)) {
            return $this->respondError([], 'Não foi possível salvar a disponibilidade.');
        }

        return $this->respondSuccess('Disponibilidade salva com sucesso.', [
            'professional_id' => $professionalId,
            'schedule'        => $this->scheduleModel->groupedForProfessional($professionalId),
        ]);
    }

    /**
     * Normaliza o payload de slots vindos do frontend.
     *
     * @return list<array{day_of_week: int, start_time: string, end_time: string}>|null
     */
    private function normalizeSlots(mixed $days): ?array
    {
        if (! is_array($days)) {
            return null;
        }

        $slots = [];

        foreach ($days as $day => $rows) {
            $day = (int) $day;

            if ($day < 0 || $day > 6 || ! is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                $start = trim((string) ($row['start_time'] ?? ''));
                $end   = trim((string) ($row['end_time'] ?? ''));

                if ($start === '' && $end === '') {
                    continue;
                }

                if (! preg_match('/^\d{2}:\d{2}$/', $start) || ! preg_match('/^\d{2}:\d{2}$/', $end)) {
                    return null;
                }

                if (strtotime($end) <= strtotime($start)) {
                    return null;
                }

                $slots[] = [
                    'day_of_week' => $day,
                    'start_time'  => $start,
                    'end_time'    => $end,
                ];
            }
        }

        return $slots;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(): array
    {
        $json = $this->request->getJSON(true);

        return is_array($json) && $json !== [] ? $json : (array) $this->request->getPost();
    }

    private function currentTenantId(): ?int
    {
        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function respondSuccess(string $message, array $data = []): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON(array_merge([
            'success'    => true,
            'message'    => $message,
            'csrf_token' => csrf_hash(),
        ], $data));
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
