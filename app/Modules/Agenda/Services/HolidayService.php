<?php

namespace App\Modules\Agenda\Services;

use App\Modules\Agenda\Models\HolidayModel;
use RuntimeException;

class HolidayService
{
    protected HolidayModel $holidayModel;

    public function __construct(?HolidayModel $holidayModel = null)
    {
        $this->holidayModel = $holidayModel ?? new HolidayModel();
    }

    /**
     * Sincroniza os feriados nacionais de um ano vindos da BrasilAPI.
     *
     * @return int quantidade de feriados sincronizados (inseridos/atualizados)
     */
    public function syncNational(?int $year = null): int
    {
        $year ??= (int) date('Y');

        $config = config('Agenda');
        $badge  = $config->holidayBadges['national'] ?? ['label' => 'Feriado Nacional', 'color' => '#ef4444'];
        $url    = rtrim((string) $config->holidayApiBaseUrl, '/') . '/' . $year;

        $json = @file_get_contents($url, false, stream_context_create([
            'http' => [
                'timeout' => 15,
                'header'  => "User-Agent: MindTrack/1.0\r\n",
            ],
        ]));

        if ($json === false) {
            throw new RuntimeException('Não foi possível consultar a API pública de feriados.');
        }

        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new RuntimeException('Resposta inválida da API pública de feriados.');
        }

        $count = 0;

        foreach ($data as $holiday) {
            $date = $holiday['date'] ?? null;
            $name = $holiday['name'] ?? 'Feriado';

            if ($date === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
                continue;
            }

            if ($this->holidayModel->upsertNational((string) $date, (string) $name, (string) $badge['label'], (string) $badge['color'])) {
                $count++;
            }
        }

        return $count;
    }
}
