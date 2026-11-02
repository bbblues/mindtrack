<?php

ini_set('memory_limit', '1024M');

/**
 * scripts/import_protocol.php
 *
 * Importa protocolos (VB-MAPP, AFLS) diretamente dos arquivos XLSX em
 * database/data/protocolos/ usando PhpSpreadsheet.
 *
 * Uso:
 *   php scripts/import_protocol.php [--dry-run] [--file=nome.xlsx]
 *       [--protocol=vb-mapp|afls] [--tenant=ID]
 *
 * - Sem --file, processa todos os .xlsx da pasta database/data/protocolos/.
 * - O tipo de protocolo e detectado pelo nome do arquivo, salvo quando
 *   --protocol e informado.
 * - O import e idempotente (upsert) e NUNCA apaga registros, preservando
 *   avaliacoes existentes.
 */

use App\Modules\Protocols\Services\AflsImporter;
use App\Modules\Protocols\Services\ProtocolImportWriter;
use App\Modules\Protocols\Services\VbMappImporter;

// Bootstrap do CodeIgniter 4 (mesmo padrao de system/util_bootstrap.php).
chdir(__DIR__ . '/..');
require __DIR__ . '/../vendor/codeigniter4/framework/system/util_bootstrap.php';

if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
    fwrite(STDERR, "PhpSpreadsheet nao encontrado. Execute: composer require phpoffice/phpspreadsheet\n");
    exit(1);
}

$options = parseOptions($GLOBALS['argv'] ?? $_SERVER['argv'] ?? []);
$dataDir = dirname(__DIR__) . '/database/data/protocolos';
$files   = resolveFiles($options, $dataDir);

if ($files === []) {
    fwrite(STDERR, "Nenhum arquivo XLSX encontrado em {$dataDir}\n");
    exit(1);
}

$writer   = new ProtocolImportWriter();
$allStats = [];

foreach ($files as $file) {
    $type = detectType($file, $options);

    if ($type === null) {
        echo '[AVISO] Tipo nao reconhecido, pulando: ' . basename($file) . PHP_EOL;
        continue;
    }

    $importer = match ($type) {
        'vb-mapp' => new VbMappImporter(),
        'afls'    => new AflsImporter(),
    };

    echo str_repeat('=', 72) . PHP_EOL;
    echo 'Arquivo : ' . basename($file) . PHP_EOL;
    echo 'Tipo    : ' . strtoupper($type) . ($options['dry_run'] ? '  [DRY-RUN - nada sera gravado]' : '') . PHP_EOL;
    echo str_repeat('=', 72) . PHP_EOL;

    try {
        $payload = $importer->import($file);

        echo 'Protocolo: ' . $payload['name'] . ' (' . $payload['view_slug'] . ')' . PHP_EOL;
        echo 'Secoes   : ' . count($payload['levels']) . PHP_EOL;

        $totalItems = 0;

        foreach ($payload['levels'] as $level) {
            $skillCount = count($level['skills'] ?? []);
            $itemCount  = 0;

            foreach ($level['skills'] ?? [] as $skill) {
                $itemCount += count($skill['items'] ?? []);
            }

            $totalItems += $itemCount;

            echo sprintf(
                '  - %-26s habilidades=%3d  itens=%d' . PHP_EOL,
                $level['section'],
                $skillCount,
                $itemCount
            );
        }

        echo 'Total de itens mapeados: ' . $totalItems . PHP_EOL . PHP_EOL;

        $result     = $writer->write($payload, $options['dry_run'], $options['tenant']);
        $allStats[] = $result['stats'];

        printStats($result['stats'], $options['dry_run']);
    } catch (\Throwable $e) {
        fwrite(STDERR, '[ERRO] ' . $e->getMessage() . PHP_EOL);

        if (getenv('CI_ENVIRONMENT') === 'development' && $e instanceof \Error) {
            fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
        }

        exit(1);
    }

    echo PHP_EOL;
}

if (count($allStats) > 1) {
    echo str_repeat('=', 72) . PHP_EOL;
    echo 'TOTAL GERAL' . PHP_EOL;
    echo str_repeat('=', 72) . PHP_EOL;
    printStats(sumStats($allStats), $options['dry_run']);
}

/**
 * @param list<string> $argv
 *
 * @return array{file: string|null, protocol: string|null, dry_run: bool, tenant: int|null}
 */
function parseOptions(array $argv): array
{
    $options = ['file' => null, 'protocol' => null, 'dry_run' => false, 'tenant' => null];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--dry-run') {
            $options['dry_run'] = true;

            continue;
        }

        if (str_starts_with($arg, '--file=')) {
            $options['file'] = substr($arg, strlen('--file='));

            continue;
        }

        if (str_starts_with($arg, '--protocol=')) {
            $options['protocol'] = strtolower(substr($arg, strlen('--protocol=')));

            continue;
        }

        if (str_starts_with($arg, '--tenant=')) {
            $options['tenant'] = (int) substr($arg, strlen('--tenant='));

            continue;
        }
    }

    return $options;
}

/**
 * @param array{file: string|null, protocol: string|null, dry_run: bool, tenant: int|null} $options
 *
 * @return list<string>
 */
function resolveFiles(array $options, string $dataDir): array
{
    if ($options['file'] !== null) {
        $path = $options['file'];

        if (! is_file($path)) {
            $path = $dataDir . DIRECTORY_SEPARATOR . basename($path);
        }

        return is_file($path) ? [$path] : [];
    }

    $files = glob($dataDir . DIRECTORY_SEPARATOR . '*.xlsx');

    return $files === false ? [] : $files;
}

/**
 * @param array{file: string|null, protocol: string|null, dry_run: bool, tenant: int|null} $options
 */
function detectType(string $file, array $options): ?string
{
    if ($options['protocol'] !== null) {
        return $options['protocol'];
    }

    $lower = strtolower(basename($file));

    if (str_contains($lower, 'vbmapp') || str_contains($lower, 'vb-mapp') || str_contains($lower, 'vb_mapp')) {
        return 'vb-mapp';
    }

    if (str_contains($lower, 'afls')) {
        return 'afls';
    }

    return null;
}

/**
 * @param array<string, int> $stats
 */
function printStats(array $stats, bool $dryRun): void
{
    $label = $dryRun ? 'Simulacao (criar/atualizar)' : 'Resultado (criados/atualizados)';

    echo $label . PHP_EOL;
    echo sprintf("  protocolos : +%d / ~%d\n", $stats['protocols_created'], $stats['protocols_updated']);
    echo sprintf("  niveis     : +%d / ~%d\n", $stats['levels_created'], $stats['levels_updated']);
    echo sprintf("  habilidades: +%d / ~%d\n", $stats['skills_created'], $stats['skills_updated']);
    echo sprintf("  itens      : +%d / ~%d  (ignorados %d)\n", $stats['items_created'], $stats['items_updated'], $stats['items_skipped']);
}

/**
 * @param list<array<string, int>> $list
 *
 * @return array<string, int>
 */
function sumStats(array $list): array
{
    $result = [];

    foreach ($list as $stats) {
        foreach ($stats as $key => $value) {
            $result[$key] = ($result[$key] ?? 0) + $value;
        }
    }

    return $result;
}

