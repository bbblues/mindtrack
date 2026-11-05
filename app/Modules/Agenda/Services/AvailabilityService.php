<?php

namespace App\Modules\Agenda\Services;

use App\Modules\Agenda\Models\ClinicScheduleModel;
use App\Modules\Agenda\Models\HolidayModel;

class AvailabilityService
{
    protected HolidayModel $holidayModel;

    protected ClinicScheduleModel $clinicScheduleModel;

    public function __construct(?HolidayModel $holidayModel = null, ?ClinicScheduleModel $clinicScheduleModel = null)
    {
        $this->holidayModel        = $holidayModel ?? new HolidayModel();
        $this->clinicScheduleModel = $clinicScheduleModel ?? new ClinicScheduleModel();
    }

    /**
     * Retorna o horário de funcionamento da clínica indexado por dia da semana.
     * Quando a clínica ainda não configurou, usa o fallback padrão do Config.
     *
     * @return array<int, array{start_time: string, end_time: string}>
     */
    public function clinicSchedule(?int $tenantId): array
    {
        $schedule = $this->clinicScheduleModel->groupedForTenant($tenantId);

        if ($schedule !== []) {
            return $schedule;
        }

        $config = config('Agenda');

        return $config->defaultClinicSchedule ?? [];
    }

    /**
     * Verifica se um agendamento está totalmente dentro do horário de
     * funcionamento da clínica naquele dia da semana.
     */
    public function isWithinClinicHours(string $start, string $end, ?int $tenantId): bool
    {
        $startTs = strtotime($start);
        $endTs   = strtotime($end);

        if ($startTs === false || $endTs === false) {
            return false;
        }

        $dayOfWeek = (int) date('w', $startTs);
        $schedule  = $this->clinicSchedule($tenantId);
        $interval  = $schedule[$dayOfWeek] ?? null;

        if ($interval === null) {
            return false;
        }

        $startMin = $this->toMinutes((string) date('H:i', $startTs));
        $endMin   = $this->toMinutes((string) date('H:i', $endTs));
        $openMin  = $this->toMinutes($interval['start_time']);
        $closeMin = $this->toMinutes($interval['end_time']);

        return $startMin >= $openMin && $endMin <= $closeMin;
    }

    /**
     * Retorna o(s) feriado(s)/fechamento(s) de uma data.
     *
     * @return list<array<string, mixed>>
     */
    public function holidaysOn(string $date, ?int $tenantId): array
    {
        return $this->holidayModel->forDate($date, $tenantId);
    }

    /**
     * Lista os motivos que impedem (ou pedem confirmação para) um agendamento.
     *
     * @return list<string>
     */
    public function impediments(string $start, string $end, ?int $tenantId): array
    {
        $reasons = [];

        $date = (string) date('Y-m-d', strtotime($start));

        foreach ($this->holidaysOn($date, $tenantId) as $holiday) {
            $prefix = ($holiday['scope'] ?? '') === 'closure' ? 'Data de fechamento' : 'Feriado';
            $reasons[] = $prefix . ': ' . (string) ($holiday['name'] ?? '');
        }

        if (! $this->isWithinClinicHours($start, $end, $tenantId)) {
            $reasons[] = 'Fora do horário de funcionamento da clínica.';
        }

        return $reasons;
    }

    /**
     * Monta o mapa usado pela agenda para destacar períodos indisponíveis.
     *
     * A disponibilidade de todos os profissionais segue o horário de
     * funcionamento da clínica; feriados/fechamentos são destacados à parte.
     *
     * @return array{holidays: array<string, array<string, mixed>>, clinicHours: array<string, list{0:string,1:string}>}
     */
    public function availabilityMap(string $start, string $end, ?int $tenantId): array
    {
        $startDate = substr($start, 0, 10);
        $endDate   = substr($end, 0, 10);
        $dates     = $this->datesBetween($startDate, $endDate);

        $holidays = [];
        foreach ($this->holidayModel->inRange($startDate, $endDate, $tenantId) as $holiday) {
            $holidays[(string) $holiday['date']] = [
                'name'  => (string) ($holiday['name'] ?? ''),
                'badge' => (string) ($holiday['badge'] ?? ''),
                'color' => (string) ($holiday['color'] ?? '#ef4444'),
                'scope' => (string) ($holiday['scope'] ?? ''),
            ];
        }

        $clinicSchedule = $this->clinicSchedule($tenantId);
        $clinicHours    = [];

        foreach ($dates as $date) {
            $dayOfWeek = (int) date('w', strtotime($date));
            $interval  = $clinicSchedule[$dayOfWeek] ?? null;

            if ($interval !== null) {
                $clinicHours[$date] = [$interval['start_time'], $interval['end_time']];
            }
        }

        return [
            'holidays'    => $holidays,
            'clinicHours' => $clinicHours,
        ];
    }

    private function toMinutes(string $time): int
    {
        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0) * 60) + (int) ($parts[1] ?? 0);
    }

    /**
     * Lista as datas entre startDate (inclusive) e endDate (exclusive).
     *
     * @return list<string>
     */
    private function datesBetween(string $startDate, string $endDate): array
    {
        $dates   = [];
        $current = strtotime($startDate);
        $limit   = strtotime($endDate);

        if ($current === false || $limit === false) {
            return [];
        }

        while ($current < $limit) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        return $dates;
    }
}
