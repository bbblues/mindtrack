<?php

namespace App\Modules\Protocols\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * VbMappImporter
 *
 * Le o XLSX do VB-MAPP e converte em payload normalizado.
 * Cada aba vira uma secao (protocol_levels.section).
 */
class VbMappImporter implements ProtocolImporterInterface
{
    /**
     * Abas (indice 1-based) => secao + ordem.
     */
    private const SECTIONS = [
        1 => ['section' => 'Marcadores', 'sort' => 1],
        2 => ['section' => 'Barreiras', 'sort' => 2],
        3 => ['section' => 'Transição', 'sort' => 3],
        4 => ['section' => 'Rastreamento Nível I', 'sort' => 4],
        5 => ['section' => 'Rastreamento Nível II', 'sort' => 5],
        6 => ['section' => 'Rastreamento Nível III', 'sort' => 6],
    ];

    private const SKILL_ORDER = [
        'Mando', 'Tato', 'Ouvinte', 'VP/MTS', 'Brincar', 'Social',
        'Imitação', 'Ecóico', 'Leitura', 'Escrita', 'LRFFC', 'Intraverbal',
        'Grupo', 'Linguística', 'Matemática', 'Vocal',
    ];

    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);

        $levels = [];

        foreach (self::SECTIONS as $index => $meta) {
            $sheet     = $spreadsheet->getSheet($index - 1);
            $levels[]  = $this->importSheet($sheet, $meta['section'], $meta['sort']);
        }

        return [
            'name'        => 'VB-MAPP',
            'view_slug'   => 'vb-mapp',
            'description' => 'Verbal Behavior Milestones Assessment and Placement Program',
            'levels'      => $levels,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importSheet(Worksheet $sheet, string $section, int $sort): array
    {
        return match ($section) {
            'Marcadores'   => $this->importMarcadores($sheet, $section, $sort),
            'Barreiras'    => $this->importBarreiras($sheet, $section, $sort),
            'Transição'    => $this->importTransicao($sheet, $section, $sort),
            default        => $this->importRastreamento($sheet, $section, $sort),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function importMarcadores(Worksheet $sheet, string $section, int $sort): array
    {
        $comments = $this->commentsByCoord($sheet);
        $bySkill  = [];

        foreach ($comments as $coord => $raw) {
            [$colLetter, $row] = Coordinate::coordinateFromString($coord);
            $col = Coordinate::columnIndexFromString($colLetter);
            $row = (int) $row;

            $milestone = (int) round((float) $sheet->getCell([1, $row])->getValue());

            if ($milestone < 1 || $milestone > 15) {
                continue;
            }

            if ($milestone >= 11) {
                $level = 'Nível 3';
                $headerRow = 9;
            } elseif ($milestone >= 6) {
                $level = 'Nível 2';
                $headerRow = 25;
            } else {
                $level = 'Nível 1';
                $headerRow = 41;
            }

            $skill = $this->cellText($sheet, $col, $headerRow);

            if ($skill === '') {
                continue;
            }

            $description = $this->cleanComment($raw);

            if ($description === null) {
                continue;
            }

            $bySkill[$skill][] = [
                'milestone'   => $milestone,
                'name'        => $milestone . '-M',
                'description' => $description,
                'level'       => $level,
                'cell'        => $coord,
            ];
        }

        $skills = [];

        foreach ($bySkill as $skillName => $rows) {
            usort($rows, static fn ($a, $b) => $b['milestone'] <=> $a['milestone']);

            $items = [];

            foreach ($rows as $i => $r) {
                $items[] = [
                    'name'        => $r['name'],
                    'description' => $r['description'],
                    'max_score'   => 1.0,
                    'sort_order'  => $i + 1,
                    'metadata'    => [
                        'source_file' => 'vb-mapp',
                        'sheet'       => $section,
                        'skill'       => $skillName,
                        'level'       => $r['level'],
                        'milestone'   => $r['milestone'],
                        'cell'        => $r['cell'],
                    ],
                ];
            }

            $skills[] = [
                'name'        => $skillName,
                'description' => null,
                'sort_order'  => 0,
                'items'       => $items,
            ];
        }

        $skills = $this->canonicalSkillSort($skills);

        foreach ($skills as $i => &$skill) {
            $skill['sort_order'] = $i + 1;
        }
        unset($skill);

        return [
            'name'        => $section,
            'section'     => $section,
            'description' => 'Marcos de desenvolvimento (milestones)',
            'sort_order'  => $sort,
            'skills'      => $skills,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importBarreiras(Worksheet $sheet, string $section, int $sort): array
    {
        $items = [];
        $n     = 0;

        foreach ([7, 16, 25, 34] as $row) {
            foreach (['C', 'H', 'M', 'R', 'W', 'AB'] as $col) {
                $name = $this->cellTextByRef($sheet, $col, $row);

                if ($name === '' || $this->isTitle($name)) {
                    continue;
                }

                $n++;
                $items[] = [
                    'name'        => $name,
                    'description' => null,
                    'max_score'   => 4.0,
                    'sort_order'  => $n,
                    'metadata'    => [
                        'source_file' => 'vb-mapp',
                        'sheet'       => $section,
                        'index'       => $n,
                        'cell'        => $col . $row,
                    ],
                ];
            }
        }

        return [
            'name'        => $section,
            'section'     => $section,
            'description' => 'Avaliação de barreiras',
            'sort_order'  => $sort,
            'skills'      => [
                [
                    'name'        => 'Barreiras',
                    'description' => null,
                    'sort_order'  => 1,
                    'items'       => $items,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importTransicao(Worksheet $sheet, string $section, int $sort): array
    {
        $items = [];
        $n     = 0;

        foreach ([7, 17, 27] as $row) {
            foreach (['C', 'H', 'M', 'R', 'W', 'AB'] as $col) {
                $name = $this->cellTextByRef($sheet, $col, $row);

                if ($name === '' || $this->isTitle($name)) {
                    continue;
                }

                $n++;
                $items[] = [
                    'name'        => $name,
                    'description' => null,
                    'max_score'   => 5.0,
                    'sort_order'  => $n,
                    'metadata'    => [
                        'source_file' => 'vb-mapp',
                        'sheet'       => $section,
                        'index'       => $n,
                        'cell'        => $col . $row,
                    ],
                ];
            }
        }

        return [
            'name'        => $section,
            'section'     => $section,
            'description' => 'Avaliação de transição',
            'sort_order'  => $sort,
            'skills'      => [
                [
                    'name'        => 'Transição',
                    'description' => null,
                    'sort_order'  => 1,
                    'items'       => $items,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importRastreamento(Worksheet $sheet, string $section, int $sort): array
    {
        $comments = $this->commentsByCoord($sheet);

        $skillCols = [];

        for ($col = 1; $col <= 40; $col++) {
            $name = $this->cellText($sheet, $col, 8);

            if ($name !== '') {
                $skillCols[$col] = $name;
            }
        }

        $skills     = [];
        $skillIndex = 0;
        $maxRow     = $sheet->getHighestRow();

        foreach ($skillCols as $col => $skillName) {
            $skillIndex++;
            $items = [];

            for ($row = 9; $row <= $maxRow; $row++) {
                $code = $this->cellText($sheet, $col, $row);

                if ($code === '') {
                    continue;
                }

                $coord       = Coordinate::stringFromColumnIndex($col) . $row;
                $description = $this->cleanComment($comments[$coord] ?? null);

                $items[] = [
                    'name'        => $code,
                    'description' => $description,
                    'max_score'   => 1.0,
                    'sort_order'  => count($items) + 1,
                    'metadata'    => [
                        'source_file' => 'vb-mapp',
                        'sheet'       => $section,
                        'skill'       => $skillName,
                        'code'        => $code,
                        'cell'        => $coord,
                    ],
                ];
            }

            $skills[] = [
                'name'        => $skillName,
                'description' => null,
                'sort_order'  => $skillIndex,
                'items'       => $items,
            ];
        }

        return [
            'name'        => $section,
            'section'     => $section,
            'description' => 'Rastreamento de habilidades (task analysis)',
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

    private function cellTextByRef(Worksheet $sheet, string $colLetter, int $row): string
    {
        return $this->cellText($sheet, Coordinate::columnIndexFromString($colLetter), $row);
    }

    /**
     * @return array<string, string>
     */
    private function commentsByCoord(Worksheet $sheet): array
    {
        $result = [];

        foreach ($sheet->getComments() as $coord => $comment) {
            $result[$coord] = $comment->getText()->getPlainText();
        }

        return $result;
    }

    private function cleanComment(?string $raw): ?string
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        $lines   = preg_split('/\R/', $raw);
        $result  = [];
        $started = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/\(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\)$/', $trimmed)) {
                $started = true;

                continue;
            }

            if ($started) {
                $result[] = $line;
            }
        }

        if ($result === [] && ! $started) {
            $result = array_values(array_filter($lines, static function ($l) {
                $t = trim($l);

                return $t !== '' && $t !== '======' && ! str_starts_with($t, 'ID#');
            }));
        }

        $text = trim(implode("\n", $result));

        if ($text === '' || preg_match('/^(dell|positvo)\s*:?\s*$/i', $text)) {
            return null;
        }

        return $text;
    }

    private function isTitle(string $name): bool
    {
        return stripos($name, 'VB-MAPP') !== false || stripos($name, 'Pontos ') === 0;
    }

    /**
     * @param list<array<string, mixed>> $skills
     *
     * @return list<array<string, mixed>>
     */
    private function canonicalSkillSort(array $skills): array
    {
        $order = array_flip(self::SKILL_ORDER);

        usort($skills, static function ($a, $b) use ($order) {
            $ia = $order[$a['name']] ?? PHP_INT_MAX;
            $ib = $order[$b['name']] ?? PHP_INT_MAX;

            return $ia <=> $ib ?: strcmp($a['name'], $b['name']);
        });

        return $skills;
    }
}


