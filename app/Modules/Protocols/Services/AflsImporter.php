<?php

namespace App\Modules\Protocols\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * AflsImporter
 *
 * Le o XLSX do AFLS e converte em payload normalizado.
 * Abas 1..6 viram secoes; a aba "CONSOLIDADO" (7) e um resumo e e ignorada.
 */
class AflsImporter implements ProtocolImporterInterface
{
    /**
     * Abas de protocolo (1-based).
     */
    private const PROTOCOL_SHEETS = [1, 2, 3, 4, 5, 6];

    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);

        $levels = [];

        foreach (self::PROTOCOL_SHEETS as $i => $index) {
            $sheet     = $spreadsheet->getSheet($index - 1);
            $levels[]  = $this->importSheet($sheet, $i + 1);
        }

        return [
            'name'        => 'AFLS',
            'view_slug'   => 'afls',
            'description' => 'The Assessment of Functional Living Skills',
            'levels'      => $levels,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importSheet(Worksheet $sheet, int $sort): array
    {
        $section = trim((string) $sheet->getTitle());

        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $maxRow = $sheet->getHighestRow();

        // 1. Achar a linha do sub-cabecalho "Qtde" (ancora da grade de itens).
        $headerRow = null;

        for ($r = 1; $r <= min($maxRow, 200); $r++) {
            for ($c = 1; $c <= $maxCol; $c++) {
                if ($this->cellText($sheet, $c, $r) === 'Qtde') {
                    $headerRow = $r;

                    break 2;
                }
            }
        }

        if ($headerRow === null) {
            return [
                'name'        => $section,
                'section'     => $section,
                'description' => null,
                'sort_order'  => $sort,
                'skills'      => [],
            ];
        }

        // 2. Colunas de cada area (posicao da coluna "Qtde").
        $areaCols = [];

        for ($c = 1; $c <= $maxCol; $c++) {
            if ($this->cellText($sheet, $c, $headerRow) === 'Qtde') {
                $areaCols[] = $c;
            }
        }

        // 3. Para cada area, ler Qtde/Tarefa/Pontuacao.
        $skills = [];

        foreach ($areaCols as $idx => $qtdeCol) {
            $tarefaCol = $qtdeCol + 1;
            $pontCol   = $qtdeCol + 2;
            $areaName  = $this->cellText($sheet, $qtdeCol, $headerRow - 1);

            $items    = [];
            $itemSort = 0;

            for ($r = $headerRow + 1; $r <= $maxRow; $r++) {
                $qtde = $this->cellText($sheet, $qtdeCol, $r);

                if ($qtde === '' || strtoupper($qtde) === 'TOTAL') {
                    break;
                }

                $tarefa = $this->cellText($sheet, $tarefaCol, $r);

                if ($tarefa === '') {
                    continue;
                }

                $maxScore = $this->cellNumber($sheet, $pontCol, $r);

                if ($maxScore === null || $maxScore <= 0) {
                    $maxScore = 1.0;
                }

                $itemSort++;
                $code = $areaName . ' ' . $qtde;

                $items[] = [
                    'name'        => $code,
                    'description' => $tarefa,
                    'max_score'   => $maxScore,
                    'sort_order'  => $itemSort,
                    'metadata'    => [
                        'source_file' => 'afls',
                        'sheet'       => $section,
                        'area'        => $areaName,
                        'qtde'        => $qtde,
                        'cell'        => Coordinate::stringFromColumnIndex($qtdeCol) . $r,
                    ],
                ];
            }

            $skills[] = [
                'name'        => $areaName !== '' ? $areaName : ('Área ' . ($idx + 1)),
                'description' => null,
                'sort_order'  => $idx + 1,
                'items'       => $items,
            ];
        }

        return [
            'name'        => $section,
            'section'     => $section,
            'description' => null,
            'sort_order'  => $sort,
            'skills'      => $skills,
        ];
    }

    private function cellText(Worksheet $sheet, int $col, int $row): string
    {
        $value = $sheet->getCell([$col, $row])->getValue();

        if ($value instanceof RichText) {
            return trim($value->getPlainText());
        }

        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function cellNumber(Worksheet $sheet, int $col, int $row): ?float
    {
        $cell = $sheet->getCell([$col, $row]);

        try {
            $value = $cell->getCalculatedValue();
        } catch (\Throwable $e) {
            $value = $cell->getValue();
        }

        if ($value instanceof RichText) {
            $s = trim($value->getPlainText());

            return is_numeric($s) ? (float) $s : null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $s = trim((string) $value);

        return is_numeric($s) ? (float) $s : null;
    }
}
