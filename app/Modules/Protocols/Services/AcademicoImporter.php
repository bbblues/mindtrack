<?php

namespace App\Modules\Protocols\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * AcademicoImporter - Avaliacao Global Academia do Autismo.
 *
 * Planilha unica com 5 areas (habilidades) lado a lado. Cada area contem
 * sub-habilidades e, dentro delas, tarefas numeradas. Cada tarefa vira um item
 * com max_score = 2 (0=NAO REALIZA, 1=EMERGENTE, 2=REALIZA).
 *
 * As 3 colunas de pontuacao por aplicacao sao ignoradas: as aplicacoes viram
 * avaliacoes (patient_assessments) distintas no futuro.
 */
class AcademicoImporter implements ProtocolImporterInterface
{
    private const AREA_COLS = [1, 9, 14, 19, 24];

    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getSheet(0);

        $levels = [];

        foreach (self::AREA_COLS as $sort => $col) {
            $areaName = $this->cellText($sheet, $col, 16);

            if ($areaName === '') {
                continue;
            }

            $levels[] = $this->importArea($sheet, $col, $areaName, $sort + 1);
        }

        return [
            'name'        => 'Avaliação Global Academia do Autismo',
            'view_slug'   => 'academico',
            'description' => 'Habilidades essenciais para crianças com TEA',
            'levels'      => $levels,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importArea(Worksheet $sheet, int $col, string $areaName, int $sort): array
    {
        $maxRow   = $sheet->getHighestRow();
        $skills   = [];
        $current  = -1;
        $skillOrd = 0;

        for ($r = 19; $r <= $maxRow; $r++) {
            $cell = $this->cellText($sheet, $col, $r);

            if ($cell === '') {
                continue;
            }

            if ($cell === 'TOTAL' || str_starts_with($cell, 'LEGENDA')) {
                break;
            }

            if (is_numeric($cell)) {
                if ($current < 0) {
                    continue;
                }

                $description = $this->cellText($sheet, $col + 1, $r);

                if ($description === '') {
                    continue;
                }

                $itemOrder = count($skills[$current]['items']) + 1;

                $skills[$current]['items'][] = [
                    'name'        => (string) (int) $cell,
                    'description' => $description,
                    'max_score'   => 2.0,
                    'sort_order'  => $itemOrder,
                    'metadata'    => [
                        'source_file'   => 'academico',
                        'area'          => $areaName,
                        'sub_habilidade' => $skills[$current]['name'],
                        'cell'          => Coordinate::stringFromColumnIndex($col) . $r,
                    ],
                ];

                continue;
            }

            // Texto => nova sub-habilidade.
            $skillOrd++;
            $current  = count($skills);
            $skills[] = [
                'name'        => $cell,
                'description' => null,
                'sort_order'  => $skillOrd,
                'items'       => [],
            ];
        }

        return [
            'name'        => $areaName,
            'section'     => $areaName,
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
}
