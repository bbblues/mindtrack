<div x-show="tab === 'avaliacoes'">
    <?php
    $formatDate = static function (?string $date): string {
        if ($date === null || $date === '') {
            return '—';
        }
        $timestamp = strtotime($date);

        return $timestamp === false ? $date : date('d/m/Y', $timestamp);
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
    ?>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-gray-700">Avaliações</h3>
            <p class="text-xs text-gray-500">Acompanhe as avaliações em andamento e finalizadas do paciente.</p>
        </div>
    </div>

    <?php if ($protocols === []): ?>
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
            Nenhum protocolo ativo cadastrado.
            <a href="<?= site_url('protocols/create') ?>" class="font-semibold underline">Crie um protocolo</a> antes de iniciar a avaliação.
        </div>
    <?php else: ?>
        <form action="<?= site_url('assessments/create') ?>" method="post" class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <?= csrf_field() ?>
            <input type="hidden" name="patient_id" value="<?= (int) ($patient['id'] ?? 0) ?>">
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-[220px] flex-1">
                    <label for="assessment-protocol-id" class="mb-1 block text-sm font-medium text-gray-700">Protocolo *</label>
                    <select id="assessment-protocol-id" name="protocol_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        <option value="">Selecione...</option>
                        <?php foreach ($protocols as $protocol): ?>
                            <option value="<?= (int) $protocol['id'] ?>"><?= esc($protocol['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Iniciar Avaliação
                </button>
            </div>
        </form>
    <?php endif; ?>

    <?php if ($assessments === []): ?>
        <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-700">Nenhuma avaliação registrada</p>
            <p class="mt-1 text-sm text-gray-500">Use o formulário acima para iniciar a primeira avaliação deste paciente.</p>
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
                                <?php else: ?>
                                    <a href="<?= site_url('assessments/show/' . (int) $assessment['id']) ?>" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700">Continuar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>