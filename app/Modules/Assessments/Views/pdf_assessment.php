<?php
$clinicName = (string) ($clinic['name'] ?? 'Clínica');
$clinicCnpj = (string) ($clinic['cnpj'] ?? '');

$patientName = (string) ($patient['name'] ?? '');
$birthDate   = $patient['birth_date'] ?? '';
$birthDate   = ($birthDate !== '' && $birthDate !== null) ? date('d/m/Y', strtotime((string) $birthDate)) : '';

$protocolName = (string) ($protocol['name'] ?? '');
$protocolDesc = (string) ($protocol['description'] ?? '');

$assessmentDate = $assessment['updated_at'] ?? ($assessment['created_at'] ?? '');
$assessmentDate = ($assessmentDate !== '' && $assessmentDate !== null) ? date('d/m/Y H:i', strtotime((string) $assessmentDate)) : '';

$professional = (string) ($professional ?? '');
$score        = (int) ($assessment['score'] ?? 0);
$statusLabel  = ($assessment['status'] ?? '') === 'completed' ? 'Finalizada' : 'Em Andamento';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 11px;
        color: #1f2937;
        margin: 0;
        padding: 24px;
    }

    .header {
        border-bottom: 2px solid #2563eb;
        padding-bottom: 12px;
        margin-bottom: 16px;
    }

    .header .clinic {
        font-size: 18px;
        font-weight: bold;
        color: #1d4ed8;
        margin: 0;
    }

    .header .doc-title {
        font-size: 13px;
        color: #374151;
        margin: 2px 0 0;
    }

    .meta {
        width: 100%;
        margin-bottom: 14px;
        border-collapse: collapse;
    }

    .meta td {
        padding: 3px 6px;
        vertical-align: top;
    }

    .meta .label {
        color: #6b7280;
        width: 140px;
    }

    h2 {
        font-size: 13px;
        color: #1d4ed8;
        margin: 18px 0 8px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 4px;
    }

    .items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }

    .items th {
        background: #eff6ff;
        color: #1e40af;
        text-align: left;
        padding: 6px 8px;
        border: 1px solid #dbeafe;
        font-size: 10px;
    }

    .items td {
        padding: 6px 8px;
        border: 1px solid #e5e7eb;
        vertical-align: top;
    }

    .items tr:nth-child(even) td {
        background: #f9fafb;
    }

    .score-box {
        margin-top: 16px;
        padding: 10px 14px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        font-size: 13px;
        font-weight: bold;
        color: #1e40af;
    }

    .footer {
        margin-top: 24px;
        font-size: 10px;
        color: #9ca3af;
        text-align: center;
    }
</style>
</head>
<body>
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

    <h2>Itens Avaliados</h2>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 22%;">Nível</th>
                <th style="width: 22%;">Habilidade</th>
                <th>Item</th>
                <th style="width: 18%;">Status</th>
                <th style="width: 8%;">Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($levels as $level): ?>
                <?php foreach ($level['skills'] as $skill): ?>
                    <?php foreach ($skill['items'] as $item): ?>
                        <?php
                        $itemStatusId = $item['status_id'] ?? null;
                        $itemStatus   = $itemStatusId !== null ? ($statusNames[(int) $itemStatusId] ?? '—') : 'Não avaliado';
                        ?>
                        <tr>
                            <td><?= esc($level['name']) ?></td>
                            <td><?= esc($skill['name']) ?></td>
                            <td><?= esc($item['name']) ?></td>
                            <td><?= esc($itemStatus) ?></td>
                            <td><?= (int) ($item['value_at_time'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="score-box">Score total: <?= $score ?></div>

    <div class="footer">
        Documento gerado em <?= esc(date('d/m/Y H:i')) ?> pelo MindTrack.
    </div>
</body>
</html>
