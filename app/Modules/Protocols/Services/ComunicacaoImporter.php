<?php

namespace App\Modules\Protocols\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * ComunicacaoImporter - Matriz de Comunicacao (Rowland, 2011).
 *
 * Cada aba de nivel (I..VII) vira uma secao; cada comportamento (A.1, B.2...)
 * vira uma habilidade; cada comportamento observavel vira um item (max_score=2).
 * Ignora ALUNO, Orientacoes, MATRIZ DE COMUNICACAO e abas de calculo.
 */
class ComunicacaoImporter implements ProtocolImporterInterface
{
    private const LEVEL_SHEETS = [2, 3, 4, 5, 6, 7, 8];

    private const LEVEL_NAMES = [
        2 => 'Comportamento Pré-intencional',
        3 => 'Comportamento Intencional',
        4 => 'Comunicação Não Convencional',
        5 => 'Comunicação Convencional',
        6 => 'Símbolos Concretos',
        7 => 'Símbolos Abstratos',
        8 => 'Linguagem',
    ];

    private const RATING_LABELS = ['NÃO OBSERVADO', 'EMERGENTE', 'DOMINADO'];

    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $levels      = [];

        foreach (self::LEVEL_SHEETS as $i => $sheetIndex) {
            $levels[] = $this->importLevel($spreadsheet->getSheet($sheetIndex), $sheetIndex, $i + 1);
        }

        return [
            'name'        => 'Matriz de Comunicação',
            'view_slug'   => 'comunicacao',
            'description' => 'Communication Matrix (Rowland, 2011)',
            'levels'      => $levels,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importLevel(Worksheet $sheet, int $sheetIndex, int $sort): array
    {
        $section = trim((string) $sheet->getTitle());
        $name    = self::LEVEL_NAMES[$sheetIndex] ?? $section;

        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $maxRow = $sheet->getHighestRow();

        $catByRatingCol = [];

        for ($c = 1; $c <= $maxCol; $c++) {
            for ($r = 1; $r <= 3; $r++) {
                $header = $this->cellText($sheet, $c, $r);

                if (preg_match('/O\s*QUE\s*OBSERVAR/i', $header)) {
                    $catByRatingCol[$c + 1] = $this->categoryName($header);

                    break;
                }
            }
        }

        $skills     = [];
        $skillOrder = 0;
        $currentIdx = -1;

        for ($r = 1; $r <= $maxRow; $r++) {
            $code         = $this->cellText($sheet, 2, $r);
            $behaviorName = $this->cellText($sheet, 3, $r);

            if (preg_match('/^[A-Z]\.\d+$/', $code) && $behaviorName !== '') {
                $skillOrder++;
                $currentIdx = count($skills);
                $skills[]   = [
                    'name'        => $code . ' ' . $this->firstLine($behaviorName),
                    'description' => null,
                    'sort_order'  => $skillOrder,
                    'items'       => [],
                ];

                continue;
            }

            if ($currentIdx < 0) {
                continue;
            }

            for ($c = 3; $c <= $maxCol - 1; $c++) {
                $rating = $this->cellText($sheet, $c, $r);

                if (! in_array($rating, self::RATING_LABELS, true)) {
                    continue;
                }

                $itemText = $this->cellText($sheet, $c + 1, $r);

                if ($itemText === '' || in_array($itemText, self::RATING_LABELS, true) || mb_strpos($itemText, 'Obs:') === 0) {
                    continue;
                }

                $categoria = $catByRatingCol[$c] ?? $name;
                $itemOrder = count($skills[$currentIdx]['items']) + 1;

                $skills[$currentIdx]['items'][] = [
                    'name'        => mb_substr($itemText, 0, 255),
                    'description' => null,
                    'max_score'   => 2.0,
                    'sort_order'  => $itemOrder,
                    'metadata'    => [
                        'source_file'   => 'comunicacao',
                        'sheet'         => $section,
                        'categoria'     => $categoria,
                        'comportamento' => $skills[$currentIdx]['name'],
                        'cell'          => Coordinate::stringFromColumnIndex($c + 1) . $r,
                    ],
                ];
            }
        }

        return [
            'name'        => $name,
            'section'     => $section,
            'description' => null,
            'sort_order'  => $sort,
            'skills'      => $skills,
        ];
    }

    private function categoryName(string $header): string
    {
        $h   = trim((string) preg_replace('/^O\s*QUE\s*OBSERVAR\s*/i', '', $header));
        $h   = trim($h, " ?\n\r\t");
        $key = mb_strtolower($h, 'UTF-8');
        $map = [
            'em seus movimentos corporais'   => 'Movimentos corporais',
            'nos primeiros sons'             => 'Primeiros sons',
            'em suas expressões faciais'     => 'Expressões faciais',
            'no contato visual'              => 'Contato visual',
            'nos gestos simples'             => 'Gestos simples',
            'nas vocalizações'               => 'Vocalizações',
            'quanto aos símbolos concretos'   => 'Símbolos concretos',
            'quanto aos símbolos abstratos'   => 'Símbolos abstratos',
            'quanto a liguagem'              => 'Linguagem',
            'quanto a linguagem'             => 'Linguagem',
        ];

        return $map[$key] ?? $h;
    }

    private function firstLine(string $text): string
    {
        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim($line);

            if ($line !== '') {
                return $line;
            }
        }

        return trim($text);
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
