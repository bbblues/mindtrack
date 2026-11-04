<?php

namespace App\Modules\Agenda\Services;

use App\Modules\Agenda\Models\ClinicScheduleModel;
use App\Modules\Agenda\Models\HolidayModel;
use App\Modules\Agenda\Models\ProfessionalScheduleModel;

class AvailabilityService
{
    protected ProfessionalScheduleModel $scheduleModel;

    protected HolidayModel $holidayModel;

    protected ClinicScheduleModel $clinicScheduleModel;

    public function __construct(?ProfessionalScheduleModel $scheduleModel = null, ?HolidayModel $holidayModel = null, ?ClinicScheduleModel $clinicScheduleModel = null)
    {
        $this->scheduleModel       = $scheduleModel ?? new ProfessionalScheduleModel();
        $this->holidayModel        = $holidayModel ?? new HolidayModel();
        $this->clinicScheduleModel = $clinicScheduleModel ?? new ClinicScheduleModel();
    }

    /**
     * Verifica se um agendamento está totalmente dentro de algum intervalo de
     * agenda do profissional naquele dia da semana.
     */
    public function isWithinSchedule(int $professionalId, string $start, string $end): bool
    {
        $startTs = strtotime($start);
        $endTs   = strtotime($end);

        if ($startTs === false || $endTs === false) {
            return false;
        }

        $dayOfWeek = (int) date('w', $startTs);
        $intervals = $this->scheduleModel->forDay($professionalId, $dayOfWeek);

        if ($intervals === []) {
            return false;
        }

        $startMin = $this->toMinutes((string) date('H:i', $startTs));
        $endMin   = $this->toMinutes((string) date('H:i', $endTs));

        foreach ($intervals as $interval) {
            $s = $this->toMinutes($interval['start_time']);
            $e = $this->toMinutes($interval['end_time']);

            // Overlap de intervalos [start, end) e [s, e).
            if ($startMin < $e && $endMin > $s) {
                return true;
            }
        }

        return false;
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
    public function impediments(int $professionalId, string $start, string $end, ?int $tenantId): array
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

        if (! $this->isWithinSchedule($professionalId, $start, $end)) {
            $reasons[] = 'Fora do horário de agenda do profissional.';
        }

        return $reasons;
    }

    /**
     * Monta o mapa usado pela agenda para destacar períodos indisponíveis.
     *
     * @param list<int> $professionalIds
     *
     * @return array{holidays: array<string, array<string, mixed>>, schedules: array<string, array<string, list<array{0:string,1:string}>>>, clinicHours: array<string, list{0:string,1:string}>}
     */
    public function availabilityMap(array $professionalIds, string $start, string $end, ?int $tenantId): array
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

        $schedules = [];

        foreach ($professionalIds as $professionalId) {
            $professionalId = (int) $professionalId;
            $byDayOfWeek    = $this->scheduleModel->groupedForProfessional($professionalId);
            $byDate         = [];

            foreach ($dates as $date) {
                $dayOfWeek = (int) date('w', strtotime($date));
                $intervals = $byDayOfWeek[$dayOfWeek] ?? [];

                if ($intervals !== []) {
                    $byDate[$date] = array_map(
                        static fn (array $interval): array => [$interval['start_time'], $interval['end_time']],
                        $intervals
                    );
                }
            }

            $schedules[(string) $professionalId] = $byDate;
        }

        return [
            'holidays'    => $holidays,
            'schedules'   => $schedules,
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
