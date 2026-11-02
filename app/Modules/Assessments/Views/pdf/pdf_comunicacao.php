<?php
/**
 * PDF específico do protocolo Matriz de Comunicação.
 *
 * - Resumo por seção (score total + porcentagem).
 * - Radar (percentual por seção) via RadarChartService.
 * - Por seção: comportamentos com a lista de observáveis e pontuações.
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
    ?>
    <div class="section-wrap">
        <h2><?= esc($sectionLabel) ?></h2>

        <?php foreach ($level['skills'] as $skill): ?>
            <?php if (($skill['items'] ?? []) === []) { continue; } ?>
            <h3><?= esc($skill['name']) ?></h3>
            <table class="items">
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Comportamento observável</th>
                        <th style="width: 110px; text-align: center;">Pontuação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($skill['items'] as $item): ?>
                        <?php $categoria = (string) ($item['metadata']['categoria'] ?? 'Geral'); ?>
                        <tr>
                            <td style="white-space: nowrap;"><?= esc($categoria) ?></td>
                            <td><?= esc((string) ($item['name'] ?? '')) ?></td>
                            <td class="num"><?= $num((float) ($item['score_achieved'] ?? 0)) ?> / <?= $num((float) ($item['max_score'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<div class="footer">
    Documento gerado em <?= esc(date('d/m/Y H:i')) ?> pelo MindTrack.
</div>
</body>
</html>
