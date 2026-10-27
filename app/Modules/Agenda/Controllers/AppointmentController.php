<?php

namespace App\Modules\Agenda\Controllers;

use App\Controllers\BaseController;
use App\Modules\Agenda\Models\AppointmentModel;
use App\Modules\Core\Models\RoomModel;
use App\Modules\Core\Models\SettingModel;
use App\Modules\Core\Models\UnitModel;
use App\Modules\Core\Models\UserModel;
use App\Modules\Patients\Models\PatientModel;
use CodeIgniter\HTTP\ResponseInterface;

class AppointmentController extends BaseController
{
    protected AppointmentModel $appointmentModel;

    protected UserModel $userModel;

    protected PatientModel $patientModel;

    protected UnitModel $unitModel;

    protected RoomModel $roomModel;

    protected SettingModel $settingModel;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->userModel        = new UserModel();
        $this->patientModel     = new PatientModel();
        $this->unitModel        = new UnitModel();
        $this->roomModel        = new RoomModel();
        $this->settingModel     = new SettingModel();
    }

    /**
     * Renderiza a grade da agenda para uma data (padrao: hoje).
     */
    public function index(): string
    {
        $date = (string) $this->request->getGet('date');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        return view('App\Modules\Agenda\Views\index', [
            'title'           => 'Agenda',
            'date'            => $date,
            'professionals'   => $this->userModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
            'patients'        => $this->patientModel->orderBy('name', 'ASC')->findAll(),
            'units'           => $this->unitModel->orderBy('name', 'ASC')->findAll(),
            'rooms'           => $this->roomModel->orderBy('name', 'ASC')->findAll(),
            'defaultDuration' => $this->defaultDurationMinutes(),
            'statusOptions'   => $this->statusOptions(),
        ]);
    }

    /**
     * Cria um agendamento (ou varios, quando recorrente).
     *
     * Retorna JSON. Em caso de conflito de horario sem force_override,
     * responde 409 com os detalhes do conflito.
     */
    public function store(): ResponseInterface
    {
        $payload = $this->requestPayload();

        $patientId      = (int) ($payload['patient_id'] ?? 0);
        $professionalId = (int) ($payload['professional_id'] ?? 0);
        $unitId         = (int) ($payload['unit_id'] ?? 0);

        if ($patientId <= 0 || $professionalId <= 0 || $unitId <= 0) {
            return $this->jsonError(422, 'Paciente, profissional e unidade sao obrigatorios.');
        }

        $startTime = $this->normalizeDateTime($payload['start_time'] ?? null);
        if ($startTime === null) {
            return $this->jsonError(422, 'A data/hora de inicio e obrigatoria.');
        }

        $endTime = $this->normalizeDateTime($payload['end_time'] ?? null);
        if ($endTime === null) {
            $endTime = date('Y-m-d H:i:s', strtotime($startTime) + ($this->defaultDurationMinutes() * 60));
        }

        if (strtotime($endTime) <= strtotime($startTime)) {
            return $this->jsonError(422, 'O horario de fim deve ser posterior ao horario de inicio.');
        }

        $status = (string) ($payload['status'] ?? 'agendado');
        if (! array_key_exists($status, $this->statusOptions())) {
            $status = 'agendado';
        }

        $isRecurring = filter_var($payload['is_recurring'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $data = [
            'patient_id'          => $patientId,
            'professional_id'     => $professionalId,
            'unit_id'             => $unitId,
            'room_id'             => $this->nullableId($payload['room_id'] ?? null),
            'start_time'          => $startTime,
            'end_time'            => $endTime,
            'status'              => $status,
            'is_recurring'        => $isRecurring ? 1 : 0,
            'recurrence_group_id' => null,
            'notes'               => $this->nullableText($payload['notes'] ?? null),
        ];

        if (! $this->validateData($data, $this->appointmentModel->getValidationRules(), $this->appointmentModel->getValidationMessages())) {
            return $this->jsonError(422, 'Dados invalidos.', $this->validator->getErrors());
        }

        $tenantId = is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
        if ($tenantId !== null) {
            $data['tenant_id'] = $tenantId;
        }

        $recurrenceEndDate = $isRecurring ? $this->normalizeDate($payload['recurrence_end_date'] ?? null) : null;
        $occurrences       = $this->buildOccurrences($data, $recurrenceEndDate);

        $forceOverride = filter_var($payload['force_override'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $forceOverride) {
            $conflicts = $this->collectConflicts($professionalId, $occurrences);

            if ($conflicts !== []) {
                return $this->response->setStatusCode(409)->setJSON([
                    'success'   => false,
                    'conflict'  => true,
                    'message'   => 'Conflito de horario para o profissional.',
                    'conflicts' => $conflicts,
                    'csrf_hash' => csrf_hash(),
                ]);
            }
        }

        $recurrenceGroupId = $isRecurring ? $this->generateUuid() : null;

        foreach ($occurrences as $occurrence) {
            $occurrence['recurrence_group_id'] = $recurrenceGroupId;

            if ($this->appointmentModel->insert($occurrence) === false) {
                return $this->jsonError(500, 'Falha ao salvar o agendamento.', $this->appointmentModel->errors());
            }
        }

        return $this->response->setJSON([
            'success'   => true,
            'message'   => $isRecurring
                ? sprintf('Agendamento recorrente criado (%d ocorrencias).', count($occurrences))
                : 'Agendamento criado com sucesso.',
            'created'   => count($occurrences),
            'csrf_hash' => csrf_hash(),
        ]);
    }

    /**
     * Retorna os agendamentos no formato de eventos do FullCalendar.
     */
    public function events(): ResponseInterface
    {
        $start = $this->normalizeDateTime($this->request->getGet('start'));
        $end   = $this->normalizeDateTime($this->request->getGet('end'));

        $appointments = $this->appointmentModel->forRange($start, $end);

        $events = [];

        foreach ($appointments as $appointment) {
            $events[] = [
                'id'    => (int) $appointment['id'],
                'title' => $appointment['patient_name'] ?? 'Paciente',
                'start' => $this->toIso8601((string) ($appointment['start_time'] ?? '')),
                'end'   => $this->toIso8601((string) ($appointment['end_time'] ?? '')),
                'color' => $this->statusColor((string) ($appointment['status'] ?? 'agendado')),
            ];
        }

        return $this->response->setJSON($events);
    }

    /**
     * Atualiza data/hora de um agendamento apos drag-and-drop no FullCalendar.
     */
    public function updateSchedule(): ResponseInterface
    {
        $payload = $this->requestPayload();

        $id = (int) ($payload['id'] ?? 0);
        if ($id <= 0) {
            return $this->jsonError(422, 'Agendamento invalido.');
        }

        $appointment = $this->appointmentModel->find($id);
        if ($appointment === null) {
            return $this->jsonError(404, 'Agendamento nao encontrado.');
        }

        $startTime = $this->normalizeDateTime($payload['start'] ?? null);
        $endTime   = $this->normalizeDateTime($payload['end'] ?? null);

        if ($startTime === null || $endTime === null) {
            return $this->jsonError(422, 'As datas/horas de inicio e fim sao obrigatorias.');
        }

        if (strtotime($endTime) <= strtotime($startTime)) {
            return $this->jsonError(422, 'O horario de fim deve ser posterior ao horario de inicio.');
        }

        $professionalId = (int) ($appointment['professional_id'] ?? 0);
        $forceOverride  = filter_var($payload['force_override'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $forceOverride) {
            $conflicts = $this->appointmentModel->findConflicts($professionalId, $startTime, $endTime, $id);

            if ($conflicts !== []) {
                return $this->response->setStatusCode(409)->setJSON([
                    'success'   => false,
                    'conflict'  => true,
                    'message'   => 'Conflito de horario para o profissional.',
                    'conflicts' => $conflicts,
                    'csrf_hash' => csrf_hash(),
                ]);
            }
        }

        if (! $this->appointmentModel->update($id, [
            'start_time' => $startTime,
            'end_time'   => $endTime,
        ])) {
            return $this->jsonError(500, 'Falha ao atualizar o agendamento.', $this->appointmentModel->errors());
        }

        return $this->response->setJSON([
            'success'   => true,
            'message'   => 'Agendamento atualizado.',
            'csrf_hash' => csrf_hash(),
        ]);
    }


    /**
     * @return array<string, mixed>
     */
    private function requestPayload(): array
    {
        try {
            $json = $this->request->getJSON(true);
        } catch (\Throwable) {
            $json = null;
        }

        if (is_array($json) && $json !== []) {
            return $json;
        }

        $post = $this->request->getPost();

        return is_array($post) ? $post : [];
    }

    private function jsonError(int $status, string $message, array $errors = []): ResponseInterface
    {
        $body = [
            'success'  => false,
            'message'  => $message,
            'csrf_hash' => csrf_hash(),
        ];

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return $this->response->setStatusCode($status)->setJSON($body);
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'confirmado'         => '#22c55e',
            'realizado'          => '#10b981',
            'falta_paciente'     => '#ef4444',
            'falta_profissional' => '#f59e0b',
            'cancelado'          => '#9ca3af',
            default              => '#3b82f6',
        };
    }

    private function toIso8601(string $datetime): string
    {
        $timestamp = strtotime($datetime);

        return $timestamp === false ? '' : date('Y-m-d\TH:i:s\Z', $timestamp);
    }

    private function nullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * Gera os horarios (slots) da grade.
     *
     * @return list<string>
     */
    private function buildTimeSlots(string $start, string $end, int $stepMinutes): array
    {
        $slots   = [];
        $current = strtotime($start);
        $limit   = strtotime($end);

        while ($current <= $limit) {
            $slots[]  = date('H:i', $current);
            $current += $stepMinutes * 60;
        }

        return $slots;
    }

    private function defaultDurationMinutes(): int
    {
        $setting = $this->settingModel->where('setting_key', 'appointment_default_duration_minutes')->first();
        $value   = $setting['setting_value'] ?? '40';

        return is_numeric($value) && (int) $value > 0 ? (int) $value : 40;
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        return [
            'agendado'           => 'Agendado',
            'confirmado'         => 'Confirmado',
            'realizado'          => 'Realizado',
            'falta_paciente'     => 'Falta do Paciente',
            'falta_profissional' => 'Falta do Profissional',
            'cancelado'          => 'Cancelado',
        ];
    }

    /**
     * Gera as ocorrencias individuais de um agendamento recorrente (semanal).
     *
     * @param array<string, mixed> $data
     *
     * @return list<array<string, mixed>>
     */
    private function buildOccurrences(array $data, ?string $recurrenceEndDate): array
    {
        $startTs  = strtotime($data['start_time']);
        $endTs    = strtotime($data['end_time']);
        $duration = max(0, $endTs - $startTs);

        $limitTs = $startTs;
        if ($recurrenceEndDate !== null) {
            $endOfDayTs = strtotime($recurrenceEndDate . ' 23:59:59');
            if ($endOfDayTs !== false && $endOfDayTs >= $startTs) {
                $limitTs = $endOfDayTs;
            }
        }

        $occurrences    = [];
        $current        = $startTs;
        $maxOccurrences = 520;

        while ($current <= $limitTs && count($occurrences) < $maxOccurrences) {
            $occurrence               = $data;
            $occurrence['start_time'] = date('Y-m-d H:i:s', $current);
            $occurrence['end_time']   = date('Y-m-d H:i:s', $current + $duration);
            $occurrences[]            = $occurrence;
            $current                  = strtotime('+7 days', $current);
        }

        return $occurrences !== [] ? $occurrences : [$data];
    }

    /**
     * Junta (sem duplicar) os conflitos de todas as ocorrencias.
     *
     * @param list<array<string, mixed>> $occurrences
     *
     * @return list<array<string, mixed>>
     */
    private function collectConflicts(int $professionalId, array $occurrences): array
    {
        $unique = [];

        foreach ($occurrences as $occurrence) {
            $conflicts = $this->appointmentModel->findConflicts(
                $professionalId,
                $occurrence['start_time'],
                $occurrence['end_time']
            );

            foreach ($conflicts as $conflict) {
                $unique[(int) $conflict['id']] = $conflict;
            }
        }

        return array_values($unique);
    }

    private function generateUuid(): string
    {
        $bytes    = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}

