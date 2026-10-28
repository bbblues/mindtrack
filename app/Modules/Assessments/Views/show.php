<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$statusLabel = ($assessment['status'] ?? '') === 'completed' ? 'Finalizada' : 'Em Andamento';
$statusBadgeClass = ($assessment['status'] ?? '') === 'completed' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700';

$statusesForJs = [];
foreach ($statuses as $status) {
    $statusesForJs[] = [
        'id'    => (int) $status['id'],
        'name'  => (string) $status['name'],
        'value' => (int) ($status['value'] ?? 0),
    ];
}

$itemStates = [];
foreach ($levels as $level) {
    foreach ($level['skills'] as $skill) {
        foreach ($skill['items'] as $item) {
            if ($item['assessment_item_id'] === null) {
                continue;
            }
            $itemStates[(string) $item['assessment_item_id']] = $item['status_id'] !== null ? (string) $item['status_id'] : '';
        }
    }
}

$hasItems = $itemStates !== [];
?>

<style>[x-cloak]{display:none!important}</style>

<div x-data="assessmentApp()" x-cloak class="space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4 p-6">
            <div>
                <a href="<?= site_url('patients') ?>" class="mb-3 inline-flex items-center gap-1 text-sm font-medium text-blue-600 transition hover:text-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Pacientes
                </a>
                <h2 class="text-xl font-semibold text-gray-800"><?= esc($patient['name']) ?></h2>
                <p class="mt-1 text-sm text-gray-500"><?= esc($protocol['name']) ?></p>
                <span class="mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusBadgeClass ?>">
                    <?= esc($statusLabel) ?>
                </span>
            </div>

            <div class="flex flex-col items-end gap-3">
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-500">Score total</p>
                    <p class="text-4xl font-bold text-blue-600" x-text="score"></p>
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="mode = 'view'"
                        :class="mode === 'view' ? 'bg-blue-600 text-white' : 'border border-gray-300 bg-white text-gray-700'"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition hover:opacity-90"
                    >
                        Visualizar
                    </button>
                    <button
                        type="button"
                        @click="mode = 'edit'"
                        :class="mode === 'edit' ? 'bg-blue-600 text-white' : 'border border-gray-300 bg-white text-gray-700'"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition hover:opacity-90"
                    >
                        Editar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if (session('success')): ?>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= esc(session('success')) ?></div>
    <?php endif; ?>

    <?php if (session('error')): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <?php if (! $hasItems): ?>
        <div class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Este protocolo ainda nao possui itens</h3>
            <p class="mt-1 max-w-sm text-sm text-gray-500">Cadastre niveis, habilidades e itens no protocolo para comecar a avaliar.</p>
            <a href="<?= site_url('protocols') ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Ir para Protocolos</a>
        </div>
    <?php else: ?>
        <?php $levelIndex = 0; ?>
        <?php foreach ($levels as $level): ?>
            <div x-data="{ open: <?= $levelIndex === 0 ? 'true' : 'false' ?> }" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-4 px-6 py-4 text-left transition hover:bg-gray-50">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-800"><?= esc($level['name']) ?></h3>
                        <?php if ($level['description'] !== ''): ?>
                            <p class="mt-0.5 text-sm text-gray-500"><?= esc($level['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <svg x-show="!open" class="h-5 w-5 shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                    <svg x-show="open" x-cloak class="h-5 w-5 shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                    </svg>
                </button>
                <div x-show="open" x-cloak class="border-t border-gray-100 px-6 py-4">
                    <?php foreach ($level['skills'] as $skill): ?>
                        <div class="mb-5 last:mb-0">
                            <h4 class="text-sm font-semibold text-blue-700"><?= esc($skill['name']) ?></h4>
                            <?php if ($skill['description'] !== ''): ?>
                                <p class="mb-1 text-xs text-gray-500"><?= esc($skill['description']) ?></p>
                            <?php endif; ?>
                            <div class="mt-2 space-y-2">
                                <?php foreach ($skill['items'] as $item): ?>
                                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-800"><?= esc($item['name']) ?></p>
                                            <?php if ($item['description'] !== ''): ?>
                                                <p class="mt-0.5 text-xs text-gray-500"><?= esc($item['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="w-full sm:w-auto">
                                            <span
                                                x-show="mode === 'view'"
                                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                                :class="statusColor(items['<?= (int) $item['assessment_item_id'] ?>'])"
                                                x-text="statusName(items['<?= (int) $item['assessment_item_id'] ?>'])"
                                            ></span>
                                            <?php if ($item['assessment_item_id'] !== null): ?>
                                                <select
                                                    x-show="mode === 'edit'"
                                                    x-cloak
                                                    data-previous="<?= $item['status_id'] !== null ? (int) $item['status_id'] : '' ?>"
                                                    @change="saveItem(<?= (int) $item['assessment_item_id'] ?>, $event.target.value, $event.target)"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 sm:w-44"
                                                >
                                                    <option value="">Nao avaliado</option>
                                                    <?php foreach ($statuses as $status): ?>
                                                        <option value="<?= (int) $status['id'] ?>" <?= $item['status_id'] !== null && (int) $item['status_id'] === (int) $status['id'] ? 'selected' : '' ?>>
                                                            <?= esc($status['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php $levelIndex++; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function assessmentApp() {
    return {
        mode: 'view',
        score: <?= (int) ($assessment['score'] ?? 0) ?>,
        csrfToken: <?= json_encode(csrf_hash()) ?>,
        statuses: <?= json_encode($statusesForJs) ?>,
        items: <?= json_encode($itemStates) ?>,

        statusName(id) {
            if (!id) {
                return 'Nao avaliado';
            }

            const status = this.statuses.find((s) => String(s.id) === String(id));

            return status ? status.name : '—';
        },

        statusColor(id) {
            if (!id) {
                return 'bg-gray-100 text-gray-500';
            }

            const status = this.statuses.find((s) => String(s.id) === String(id));

            if (!status) {
                return 'bg-gray-100 text-gray-500';
            }

            return status.value > 0 ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700';
        },

        async saveItem(assessmentItemId, statusId, selectEl) {
            const previous = selectEl && selectEl.dataset ? (selectEl.dataset.previous ?? '') : '';

            try {
                const response = await fetch(<?= json_encode(site_url('assessments/update-item')) ?>, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        assessment_item_id: assessmentItemId,
                        status_id: statusId,
                    }),
                });

                const data = await response.json();

                if (data.csrf_hash) {
                    this.csrfToken = data.csrf_hash;
                }

                if (!data.success) {
                    alert(data.message || 'Erro ao salvar o item.');
                    this.items[assessmentItemId] = previous;
                    if (selectEl) {
                        selectEl.value = previous;
                    }
                    return;
                }

                this.score = data.score;
                this.items[assessmentItemId] = statusId;

                if (selectEl) {
                    selectEl.dataset.previous = statusId;
                }
            } catch (error) {
                alert('Erro ao salvar o item.');
                this.items[assessmentItemId] = previous;
                if (selectEl) {
                    selectEl.value = previous;
                }
            }
        },
    };
}
</script>

<?= $this->endSection() ?>



