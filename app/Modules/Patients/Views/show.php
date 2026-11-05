<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$p = $patient ?? [];
$postTypes = $postTypes ?? [];

$maskCpf = static function (?string $cpf): string {
    if ($cpf === null || $cpf === '') {
        return '—';
    }
    $digits = preg_replace('/\D/', '', $cpf);
    if (strlen($digits) !== 11) {
        return $cpf;
    }

    return vsprintf('%s.%s.%s-%s', str_split($digits, 3));
};

$formatDate = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '—';
    }
    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('d/m/Y', $timestamp);
};

$formatDateTime = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '—';
    }
    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('d/m/Y H:i', $timestamp);
};

$formatScore = static function (mixed $score): string {
    if ($score === null || $score === '') {
        return '—';
    }
    $number = (float) $score;
    if ($number === floor($number)) {
        return (string) (int) $number;
    }

    return rtrim(rtrim(number_format($number, 2, ',', '.'), '0'), ',');
};

$maskZip = static function (?string $zip): string {
    if ($zip === null || $zip === '') {
        return '—';
    }
    $digits = preg_replace('/\D/', '', $zip);
    if (strlen($digits) !== 8) {
        return $zip;
    }

    return substr($digits, 0, 5) . '-' . substr($digits, 5);
};

$initials = static function (?string $name): string {
    $name = trim((string) $name);
    if ($name === '') {
        return '?';
    }
    $parts = preg_split('/\s+/', $name);
    $first = mb_substr((string) $parts[0], 0, 1);
    $last  = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : '';

    return mb_strtoupper($first . $last);
};

$field = static fn (string $label, mixed $value): string =>
    '<div>' .
    '<dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">' . esc($label) . '</dt>' .
    '<dd class="mt-1 text-sm text-gray-800">' . esc((string) ($value ?? '—')) . '</dd>' .
    '</div>';

$sexLabels = ['M' => 'Masculino', 'F' => 'Feminino', 'O' => 'Outro'];
$sexLabel  = $sexLabels[$p['sex'] ?? ''] ?? '—';

$isActive = (int) ($p['is_active'] ?? 1) === 1;

$typeBadgeClasses = [
    'evolution'     => 'bg-blue-100 text-blue-700',
    'communication' => 'bg-green-100 text-green-700',
    'family'        => 'bg-purple-100 text-purple-700',
    'school'        => 'bg-amber-100 text-amber-700',
];

$professionalById = [];
foreach (($professionals ?? []) as $professional) {
    $professionalById[(int) $professional['id']] = $professional;
}

$linkedProfessionals = [];
foreach (($professionalIds ?? []) as $professionalId) {
    $professionalId = (int) $professionalId;
    if (isset($professionalById[$professionalId])) {
        $linkedProfessionals[] = $professionalById[$professionalId];
    }
}
$primaryProfessionalId = (int) ($primaryProfessionalId ?? 0);

$milestonesByYear = [];
foreach (($milestones ?? []) as $milestone) {
    $year = substr((string) ($milestone['event_date'] ?? ''), 0, 4);
    if ($year === '') {
        $year = 'Sem data';
    }
    $milestonesByYear[$year][] = $milestone;
}
krsort($milestonesByYear);
?>
<style>[x-cloak]{display:none!important}</style>

<div x-data="{ tab: 'pessoal' }" x-cloak class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="inline-block h-12 w-12 rounded-full ring-2 ring-white shadow" style="background-color: <?= esc($p['color'] ?? '#3b82f6') ?>;"></span>
            <div>
                <h2 class="text-xl font-semibold text-gray-800"><?= esc($p['name'] ?? 'Paciente') ?></h2>
                <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $isActive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                    <?= $isActive ? 'Ativo' : 'Inativo' ?>
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($canEdit): ?>
                <a href="<?= site_url('patients/edit/' . (int) $p['id']) ?>" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Editar
                </a>
            <?php endif; ?>
            <a href="<?= site_url('patients') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Voltar</a>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap gap-1 border-b border-gray-200 px-4">
            <?php
            $tabs = [
                'pessoal'       => 'Pessoal',
                'clinico'       => 'Clínico',
                'endereco'      => 'Endereço',
                'responsaveis'  => 'Responsáveis',
                'profissionais' => 'Profissionais',
                'mural'         => 'Mural',
                'jornada'       => 'Jornada',
                'avaliacoes'    => 'Avaliações',
            ];
            ?>
            <?php foreach ($tabs as $tabKey => $tabLabel): ?>
                <button
                    type="button"
                    @click="tab = '<?= esc($tabKey, 'attr') ?>'"
                    :class="tab === '<?= esc($tabKey, 'attr') ?>' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="border-b-2 px-4 py-3 text-sm font-medium"
                ><?= esc($tabLabel) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="p-6">
            <div x-show="tab === 'pessoal'">
                <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                    <?= $field('Nome', $p['name'] ?? null) ?>
                    <?= $field('Data de Nascimento', $formatDate($p['birth_date'] ?? null)) ?>
                    <?= $field('CPF', $maskCpf($p['cpf'] ?? null)) ?>
                    <?= $field('Sexo', $sexLabel) ?>
                    <?= $field('Unidade', $unitName ?? null) ?>
                    <?= $field('Status', $isActive ? 'Ativo' : 'Inativo') ?>
                </dl>
            </div>

            <div x-show="tab === 'clinico'">
                <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                    <?= $field('Diagnóstico', $p['diagnosis'] ?? null) ?>
                    <?= $field('Nível de Suporte', $p['support_level'] ?? null) ?>
                    <?= $field('Convênio', $insuranceName ?? null) ?>
                    <?= $field('Nº da Carteirinha', $p['insurance_number'] ?? null) ?>
                    <?= $field('Tags', $p['tags'] ?? null) ?>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Alergias</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-gray-800"><?= esc((string) ($p['allergies'] ?? '') !== '' ? $p['allergies'] : '—') ?></dd>
                    </div>
                </dl>
            </div>
            <div x-show="tab === 'endereco'">
                <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                    <?= $field('CEP', $maskZip($p['zip_code'] ?? null)) ?>
                    <?= $field('Rua', $p['street'] ?? null) ?>
                    <?= $field('Número', $p['number'] ?? null) ?>
                    <?= $field('Complemento', $p['complement'] ?? null) ?>
                    <?= $field('Bairro', $p['neighborhood'] ?? null) ?>
                    <?= $field('Cidade', $p['city'] ?? null) ?>
                    <?= $field('UF', $p['state'] ?? null) ?>
                </dl>
            </div>

            <div x-show="tab === 'responsaveis'">
                <?php if (($guardians ?? []) === []): ?>
                    <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Nenhum responsável cadastrado</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <?php foreach ($guardians as $guardian): ?>
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-800"><?= esc((string) ($guardian['name'] ?? '—')) ?></p>
                                    <?php if ((int) ($guardian['is_primary'] ?? 0) === 1): ?>
                                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">Principal</span>
                                    <?php endif; ?>
                                    <?php if ((int) ($guardian['is_financial'] ?? 0) === 1): ?>
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Financeiro</span>
                                    <?php endif; ?>
                                </div>
                                <dl class="mt-3 space-y-2 text-sm text-gray-600">
                                    <?php if ((string) ($guardian['relationship'] ?? '') !== ''): ?>
                                        <div><span class="text-gray-400">Parentesco:</span> <?= esc((string) $guardian['relationship']) ?></div>
                                    <?php endif; ?>
                                    <?php if ((string) ($guardian['cpf'] ?? '') !== ''): ?>
                                        <div><span class="text-gray-400">CPF:</span> <?= esc($maskCpf((string) $guardian['cpf'])) ?></div>
                                    <?php endif; ?>
                                    <?php if ((string) ($guardian['phone'] ?? '') !== ''): ?>
                                        <div><span class="text-gray-400">Telefone:</span> <?= esc((string) $guardian['phone']) ?></div>
                                    <?php endif; ?>
                                    <?php if ((string) ($guardian['email'] ?? '') !== ''): ?>
                                        <div><span class="text-gray-400">E-mail:</span> <?= esc((string) $guardian['email']) ?></div>
                                    <?php endif; ?>
                                </dl>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div x-show="tab === 'profissionais'">
                <?php if ($linkedProfessionals === []): ?>
                    <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Nenhum profissional vinculado</p>
                    </div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-100">
                        <?php foreach ($linkedProfessionals as $professional): ?>
                            <li class="flex items-center justify-between py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700"><?= esc($initials($professional['name'] ?? null)) ?></div>
                                    <span class="text-sm font-medium text-gray-800"><?= esc($professional['name'] ?? '—') ?></span>
                                </div>
                                <?php if ((int) ($professional['id'] ?? 0) === $primaryProfessionalId): ?>
                                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">Responsável principal</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div x-show="tab === 'mural'">
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-700">Mural</h3>
                    <p class="text-xs text-gray-500">Publicações registradas para este paciente.</p>
                </div>

                <?php if (($posts ?? []) === []): ?>
                    <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Nenhuma publicação</p>
                    </div>
                <?php else: ?>
                    <ol class="space-y-4">
                        <?php foreach ($posts as $post): ?>
                            <?php $badgeClass = $typeBadgeClasses[$post['type'] ?? ''] ?? 'bg-gray-100 text-gray-700'; ?>
                            <li class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700"><?= esc($initials($post['user_name'] ?? null)) ?></div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800"><?= esc($post['user_name'] ?? 'Usuário') ?></p>
                                            <p class="text-xs text-gray-500"><?= esc($post['user_role'] ?? 'Profissional') ?></p>
                                        </div>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $badgeClass ?>"><?= esc($postTypes[$post['type'] ?? ''] ?? ($post['type'] ?? '—')) ?></span>
                                </div>
                                <p class="mt-3 whitespace-pre-line text-sm text-gray-700"><?= esc((string) ($post['content'] ?? '')) ?></p>
                                <p class="mt-2 text-xs text-gray-400"><?= esc($formatDateTime($post['created_at'] ?? null)) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
            <div x-show="tab === 'jornada'">
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-700">Jornada do Paciente</h3>
                    <p class="text-xs text-gray-500">Marcos e conquistas ao longo do tratamento.</p>
                </div>

                <?php if (($milestones ?? []) === []): ?>
                    <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Nenhum marco registrado</p>
                    </div>
                <?php else: ?>
                    <ol class="relative space-y-8 border-l border-gray-200 pl-6">
                        <?php foreach ($milestonesByYear as $year => $yearMilestones): ?>
                            <li class="list-none">
                                <h4 class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <?= esc((string) $year) ?>
                                </h4>
                                <ol class="space-y-6">
                                    <?php foreach ($yearMilestones as $milestone): ?>
                                        <li class="relative">
                                            <span class="absolute -left-[31px] top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 ring-4 ring-white"></span>
                                            <div class="flex items-center gap-2">
                                                <?php if ((string) ($milestone['icon'] ?? '') !== ''): ?>
                                                    <span class="text-lg"><?= esc((string) $milestone['icon']) ?></span>
                                                <?php endif; ?>
                                                <p class="text-sm font-semibold text-gray-800"><?= esc((string) ($milestone['title'] ?? '—')) ?></p>
                                            </div>
                                            <?php if ((string) ($milestone['description'] ?? '') !== ''): ?>
                                                <p class="mt-1 whitespace-pre-line text-sm text-gray-600"><?= esc((string) $milestone['description']) ?></p>
                                            <?php endif; ?>
                                            <p class="mt-1 text-xs text-gray-400"><?= esc($formatDate($milestone['event_date'] ?? null)) ?></p>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
            <div x-show="tab === 'avaliacoes'">
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-700">Avaliações</h3>
                    <p class="text-xs text-gray-500">Avaliações em andamento e finalizadas do paciente.</p>
                </div>

                <?php if (($assessments ?? []) === []): ?>
                    <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Nenhuma avaliação registrada</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Protocolo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Data de Início</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Score</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($assessments as $assessment): ?>
                                    <?php
                                    $isCompleted      = ($assessment['status'] ?? '') === 'completed';
                                    $statusLabel      = $isCompleted ? 'Finalizada' : 'Em Andamento';
                                    $statusBadgeClass = $isCompleted ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700';
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-800"><?= esc($assessment['protocol_name'] ?? '—') ?></td>
                                        <td class="px-4 py-3 text-gray-600"><?= esc($formatDate($assessment['created_at'] ?? null)) ?></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusBadgeClass ?>">
                                                <?= esc($statusLabel) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600"><?= esc($formatScore($assessment['score'] ?? null)) ?></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <?php if ($isCompleted): ?>
                                                <a href="<?= site_url('assessments/show/' . (int) $assessment['id']) ?>" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Ver Relatório</a>
                                            <?php elseif ($canManageAssessments): ?>
                                                <a href="<?= site_url('assessments/show/' . (int) $assessment['id']) ?>" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700">Ver Avaliação</a>
                                            <?php else: ?>
                                                <span class="text-xs font-medium text-gray-400">Em andamento</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>





