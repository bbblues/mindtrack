<?php

namespace App\Commands;

use App\Modules\Agenda\Services\HolidayService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Sincroniza os feriados nacionais (BrasilAPI) para o ano corrente e os
 * anos seguintes configurados em Config\Agenda::$holidaySyncYearsAhead.
 *
 * Uso: php spark holidays:sync-national [ano]
 */
class HolidaySync extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'holidays:sync-national';
    protected $usage       = 'holidays:sync-national [ano]';
    protected $description = 'Sincroniza os feriados nacionais via BrasilAPI (cache local no banco).';

    /**
     * @param array<array-key, string|null> $params
     *
     * @return int
     */
    public function run(array $params)
    {
        $year       = isset($params[0]) && ctype_digit((string) $params[0]) ? (int) $params[0] : (int) date('Y');
        $config     = config('Agenda');
        $yearsAhead = max(0, (int) ($config->holidaySyncYearsAhead ?? 1));
        $service    = new HolidayService();
        $total      = 0;

        for ($offset = 0; $offset <= $yearsAhead; $offset++) {
            $targetYear = $year + $offset;

            try {
                $count = $service->syncNational($targetYear);
                $total += $count;
                CLI::write(sprintf('Ano %d: %d feriado(s) sincronizado(s).', $targetYear, $count), 'green');
            } catch (\Throwable $e) {
                CLI::write(sprintf('Ano %d: falha — %s', $targetYear, $e->getMessage()), 'red');
            }
        }

        CLI::write(sprintf('Total: %d feriado(s).', $total), 'green');

        return EXIT_SUCCESS;
    }
}
