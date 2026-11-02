<?php
/**
 * PDF específico do protocolo VB-MAPP.
 *
 * - Resumo por seção (score total + porcentagem).
 * - Radar (percentual por seção) via RadarChartService.
 * - Grid (Marcadores e Rastreamento) com células verdes (1) e cinzas (0).
 * - Lista com notas (Barreiras e Transição).
 *
 * @var array<string, mixed> $assessment
 * @var array<string, mixed> $patient
 * @var array<string, mixed> $protocol
 * @var array<string, mixed>|null $clinic
 * @var array<string, mixed> $scores
 * @var list<array<string, mixed>> $levels
 * @var list<array{key: string, label: string, score: float, max: float, percentage: float}> $sectionSummaries
 * @var string $radarImage
 * @var string $professional
 */
$num = static function (float $value): string {
    return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
};

$labelByKey = [];
foreach ($sectionSummaries as $summary) {
    $labelByKey[$summary['key']] = $summary['label'];
}

$typeOf = static function (string $section, string $name): string {
    $label = mb_strtolower($section !== '' ? $section : $name, 'UTF-8');

    if (str_contains($label, 'barreira')) {
        return 'barriers';
    }

    if (str_contains($label, 'transi')) {
        return 'transition';
    }

    if (str_contains($label, 'marcador')) {
        return 'milestones';
    }

    if (str_contains($label, 'rastreamento')) {
        return 'tracking';
    }

    return 'list';
};

$cellClass = static function (?array $cell): string {
    if ($cell === null || $cell['assessment_item_id'] === null) {
        return 'cell-empty';
    }

    return ((float) ($cell['score_achieved'] ?? 0)) > 0 ? 'cell-1' : 'cell-0';
};

$cellLabel = static function (?array $cell): string {
    if ($cell === null || $cell['assessment_item_id'] === null) {
        return '';
    }

    $code = trim((string) ($cell['metadata']['code'] ?? ''));

    if ($code === '') {
        $code = trim((string) ($cell['name'] ?? ''));
    }

    return $code;
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<?= view('App\Modules\Assessments\Views\pdf\_pdf_styles') ?>
</head>
<body>
<?= view('App\Modules\Assessments\Views\pdf\_pdf_header', [
    'clinic'       => $clinic,
    'patient'      => $patient,
    'protocol'     => $protocol,
    'professional' => $professional,
    'assessment'   => $assessment,
    'scores'       => $scores,
    'radarImage'   => $radarImage,
]) ?>

<h2>Resumo por seção</h2>
<table class="items">
    <thead>
        <tr>
            <th>Seção</th>
            <th style="width: 160px; text-align: center;">Score Total</th>
            <th style="width: 110px; text-align: center;">Porcentagem</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($sectionSummaries as $summary): ?>
            <tr>
                <td><?= esc($summary['label']) ?></td>
                <td class="num"><?= $num($summary['score']) ?> / <?= $num($summary['max']) ?></td>
                <td class="num"><?= $num($summary['percentage']) ?>%</td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php foreach ($levels as $level): ?>
    <?php
    $sectionKey   = $level['section'] !== '' ? $level['section'] : $level['name'];
    $sectionLabel = $labelByKey[$sectionKey] ?? $sectionKey;
    $type         = $typeOf($level['section'], $level['name']);
    ?>
    <div class="section-wrap">
        <h2><?= esc($sectionLabel) ?></h2>

        <?php if ($type === 'milestones'): ?>
            <?php
            $skillNames = [];
            $cells      = [];
            $milestones = [];

            foreach ($level['skills'] as $skill) {
                $skillNames[] = (string) $skill['name'];

                foreach ($skill['items'] as $item) {
                    $milestone = (int) ($item['metadata']['milestone'] ?? 0);

                    if ($milestone <= 0) {
                        continue;
                    }

                    $milestones[$milestone]                      = true;
                    $cells[$milestone][(string) $skill['name']] = $item;
                }
            }
            ksort($milestones);

            // 16 habilidades não cabem em uma única linha A4. Dividimos em
            // blocos de 8 para manter os cabeçalhos legíveis (sem quebra de palavra).
            $skillChunks = array_chunk($skillNames, 8);
            ?>
            <div class="legend">
                <span class="swatch green"></span> 1 (atingido) &nbsp;&nbsp;
                <span class="swatch gray"></span> 0 (não atingido) &nbsp;&nbsp;
                <span class="swatch empty"></span> não aplicável
            </div>
            <?php foreach ($skillChunks as $chunk): ?>
                <table class="grid milestones">
                    <thead>
                        <tr>
                            <th style="width: 46px;">Marco</th>
                            <?php foreach ($chunk as $skillName): ?>
                                <th><?= esc($skillName) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $currentGroup = null; ?>
                        <?php foreach (array_keys($milestones) as $milestone): ?>
                            <?php $group = $milestone <= 5 ? 'Nível 1' : ($milestone <= 10 ? 'Nível 2' : 'Nível 3'); ?>
                            <?php if ($group !== $currentGroup): ?>
                                <?php $currentGroup = $group; ?>
                                <tr class="group-row"><td colspan="<?= count($chunk) + 1 ?>"><?= esc($group) ?></td></tr>
                            <?php endif; ?>
                            <tr>
                                <td class="rowhead"><?= (int) $milestone ?>-M</td>
                                <?php foreach ($chunk as $skillName): ?>
                                    <?php $cell = $cells[$milestone][$skillName] ?? null; ?>
                                    <td class="<?= $cellClass($cell) ?>"></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>

        <?php elseif ($type === 'tracking'): ?>
            <?php
            $skillNames = [];
            $cells      = [];
            $maxSteps   = 0;

            foreach ($level['skills'] as $skill) {
                $skillNames[] = (string) $skill['name'];

                foreach ($skill['items'] as $item) {
                    $step = (int) ($item['sort_order'] ?? 0);

                    if ($step <= 0) {
                        continue;
                    }

                    $cells[$step][(string) $skill['name']] = $item;

                    if ($step > $maxSteps) {
                        $maxSteps = $step;
                    }
                }
            }
            ?>
            <div class="legend">
                <span class="swatch green"></span> 1 (atingido) &nbsp;&nbsp;
                <span class="swatch gray"></span> 0 (não atingido) &nbsp;&nbsp;
                <span class="swatch empty"></span> não aplicável
            </div>
            <table class="grid">
                <thead>
                    <tr>
                        <th style="width: 46px;">Passo</th>
                        <?php foreach ($skillNames as $skillName): ?>
                            <th><?= esc($skillName) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($step = 1; $step <= $maxSteps; $step++): ?>
                        <tr>
                            <td class="rowhead"><?= (int) $step ?></td>
                            <?php foreach ($skillNames as $skillName): ?>
                                <?php $cell = $cells[$step][$skillName] ?? null; ?>
                                <td class="<?= $cellClass($cell) ?>"><?= esc($cellLabel($cell)) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

        <?php else: ?>
            <table class="items">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="width: 100px; text-align: center;">Nota</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($level['skills'] as $skill): ?>
                        <?php foreach ($skill['items'] as $item): ?>
                            <tr>
                                <td><?= esc($item['name']) ?></td>
                                <td class="num"><?= $num((float) ($item['score_achieved'] ?? 0)) ?> / <?= $num((float) ($item['max_score'] ?? 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endforeach; ?>


<div class="footer">
    Documento gerado em <?= esc(date('d/m/Y H:i')) ?> pelo MindTrack.
</div>
</body>
</html>
