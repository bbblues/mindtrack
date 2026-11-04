<?php

namespace App\Modules\Agenda\Controllers;

use App\Controllers\BaseController;
use App\Modules\Agenda\Models\ClinicScheduleModel;
use CodeIgniter\HTTP\ResponseInterface;

class ClinicScheduleController extends BaseController
{
    protected ClinicScheduleModel $scheduleModel;

    public function __construct()
    {
        $this->scheduleModel = new ClinicScheduleModel();
    }

    /**
     * Tela de configuração do horário de funcionamento da clínica.
     */
    public function index(): string
    {
        $tenantId = $this->currentTenantId();
        $schedule = $this->scheduleModel->groupedForTenant($tenantId);

        if ($schedule === []) {
            $config   = config('Agenda');
            $schedule = $config->defaultClinicSchedule ?? [];
        }

        return view('App\Modules\Agenda\Views\settings\clinic_schedule', [
            'title'    => 'Horário de Funcionamento',
            'schedule' => $schedule,
        ]);
    }

    /**
     * Salva (substitui) o horário de funcionamento da clínica.
     */
    public function store(): ResponseInterface
    {
        $payload = $this->requestPayload();

        $slots = $this->normalizeDays($payload['days'] ?? null);

        if ($slots === null) {
            return $this->respondError([], 'Informe horários válidos para o funcionamento da clínica.');
        }

        $tenantId = $this->currentTenantId();

        if (! $this->scheduleModel->replaceForTenant($tenantId, $slots)) {
            return $this->respondError([], 'Não foi possível salvar o horário de funcionamento.');
        }

        return $this->respondSuccess('Horário de funcionamento salvo com sucesso.', [
            'schedule' => $this->scheduleModel->groupedForTenant($tenantId),
        ]);
    }

    /**
     * Normaliza o payload de dias vindos do frontend (um turno por dia).
     *
     * @return list<array{day_of_week: int, start_time: string, end_time: string}>|null
     */
    private function normalizeDays(mixed $days): ?array
    {
        if (! is_array($days)) {
            return null;
        }

        $slots = [];

        foreach ($days as $day => $row) {
            $day = (int) $day;

            if ($day < 0 || $day > 6 || ! is_array($row)) {
                continue;
            }

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
