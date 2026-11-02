<?php
/**
 * Cabeçalho comum dos PDFs de avaliação (clínica, paciente, radar, score).
 *
 * @var array<string, mixed> $assessment
 * @var array<string, mixed> $patient
 * @var array<string, mixed> $protocol
 * @var array<string, mixed>|null $clinic
 * @var array<string, mixed> $scores
 * @var string $radarImage
 * @var string $professional
 */
$clinicName = (string) ($clinic['name'] ?? 'Clínica');
$clinicCnpj = (string) ($clinic['cnpj'] ?? '');

$patientName = (string) ($patient['name'] ?? '');
$birthDate   = $patient['birth_date'] ?? '';
$birthDate   = ($birthDate !== '' && $birthDate !== null) ? date('d/m/Y', strtotime((string) $birthDate)) : '';

$protocolName = (string) ($protocol['name'] ?? '');
$protocolDesc = (string) ($protocol['description'] ?? '');

$assessmentDate = $assessment['updated_at'] ?? ($assessment['created_at'] ?? '');
$assessmentDate = ($assessmentDate !== '' && $assessmentDate !== null) ? date('d/m/Y H:i', strtotime((string) $assessmentDate)) : '';

$professional  = (string) ($professional ?? '');
$statusLabel   = ($assessment['status'] ?? '') === 'completed' ? 'Finalizada' : 'Em Andamento';

$totalScore    = (float) ($scores['total_score'] ?? ($assessment['score'] ?? 0));
$totalPossible = (float) ($scores['total_possible'] ?? 0);
$percentage    = (float) ($scores['percentage'] ?? 0);

$fmt = static function (float $value): string {
    return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
};
?>
<div class="header">
    <p class="clinic"><?= esc($clinicName) ?></p>
    <?php if ($clinicCnpj !== ''): ?>
        <p class="doc-title">CNPJ: <?= esc($clinicCnpj) ?></p>
    <?php endif; ?>
    <p class="doc-title">Relatório de Avaliação</p>
</div>

<table class="meta">
    <tr>
        <td class="label">Paciente:</td>
        <td><?= esc($patientName) ?></td>
        <td class="label">Data de Nascimento:</td>
        <td><?= esc($birthDate) ?></td>
    </tr>
    <tr>
        <td class="label">Protocolo:</td>
        <td><?= esc($protocolName) ?></td>
        <td class="label">Status:</td>
        <td><?= esc($statusLabel) ?></td>
    </tr>
    <tr>
        <td class="label">Profissional:</td>
        <td><?= esc($professional) ?></td>
        <td class="label">Data da Avaliação:</td>
        <td><?= esc($assessmentDate) ?></td>
    </tr>
    <?php if ($protocolDesc !== ''): ?>
        <tr>
            <td class="label">Descrição:</td>
            <td colspan="3"><?= esc($protocolDesc) ?></td>
        </tr>
    <?php endif; ?>
</table>

<?php if ($radarImage !== ''): ?>
<div class="radar-block">
    <h2 style="text-align:left;">Percentual por seção</h2>
    <img class="radar" src="<?= $radarImage ?>" />
</div>
<?php endif; ?>

<div class="score-box">
    Score total: <?= $fmt($totalScore) ?> / <?= $fmt($totalPossible) ?>
    &middot; <?= $fmt($percentage) ?>%
</div>
