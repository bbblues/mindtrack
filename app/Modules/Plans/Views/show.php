<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$isArchived = ($plan['status'] ?? '') === 'archived';
$planStatusLabel = $isArchived ? 'Arquivado' : 'Ativo';
$planStatusClass = $isArchived ? 'bg-gray-100 text-gray-600' : 'bg-green-100 text-green-700';

$formatDate = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '—';
    }
    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('d/m/Y', $timestamp);
};
?>

<style>[x-cloak]{display:none!important}</style>

<div x-data="planApp()" x-cloak class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div>
            <a href="<?= site_url('patients') ?>" class="mb-3 inline-flex items-center gap-1 text-sm font-medium text-blue-600 transition hover:text-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Pacientes
            </a>
            <h2 class="text-xl font-semibold text-gray-800"><?= esc($patient['name']) ?></h2>
            <p class="mt-1 text-sm text-gray-500">Plano Terapêutico</p>
            <span class="mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $planStatusClass ?>">
                <?= esc($planStatusLabel) ?>
            </span>
        </div>

        <?php if ($plan !== null): ?>
            <div class="text-right">
                <p class="text-sm font-medium text-gray-500">Início</p>
                <p class="text-lg font-semibold text-gray-800"><?= esc($formatDate($plan['start_date'] ?? null)) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <?php if (session('success')): ?>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= esc(session('success')) ?></div>
    <?php endif; ?>

    <?php if (session('error')): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <?php if ($items === []): ?>
        <div class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Nenhuma meta no plano</h3>
            <p class="mt-1 max-w-sm text-sm text-gray-500">Finalize uma avaliação e clique em "Gerar Plano Terapêutico" para importar os itens "Errados/Não Avaliados".</p>
            <a href="<?= site_url('patients') ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Ir para Pacientes</a>
        </div>
    <?php else: ?>
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Nível</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Habilidade</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Objetivo</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($items as $item): ?>
                        <?php $acquired = ($item['status'] ?? '') === 'acquired'; ?>
                        <tr class="transition hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-700"><?= esc($item['level_name'] ?? '—') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?= esc($item['skill_name'] ?? '—') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?= esc($item['objective'] ?? ($item['item_name'] ?? 'Meta')) ?></td>
                            <td class="px-6 py-4">
                                <span data-item-status class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $acquired ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' ?>">
                                    <?= $acquired ? 'Adquirido' : 'Em Andamento' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    type="button"
                                    data-toggle-item
                                    data-item-id="<?= (int) $item['id'] ?>"
                                    data-status="<?= esc($item['status'] ?? 'in_progress') ?>"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                >
                                    <?= $acquired ? 'Marcar Em Andamento' : 'Marcar Adquirido' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function planApp() {
    return {};
}

document.addEventListener('DOMContentLoaded', () => {
    let token = <?= json_encode(csrf_hash()) ?>;
    const baseUrl = <?= json_encode(site_url('plans/toggle-item/')) ?>;

    document.querySelectorAll('[data-toggle-item]').forEach((button) => {
        button.addEventListener('click', async () => {
            const id = button.dataset.itemId;

            try {
                const response = await fetch(`${baseUrl}${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({}),
                });

                const data = await response.json();

                if (data.csrf_hash) {
                    token = data.csrf_hash;
                }

                if (! data.success) {
                    alert(data.message || 'Erro ao atualizar a meta.');
                    return;
                }

                const acquired = data.status === 'acquired';
                button.dataset.status = data.status;
                button.textContent = acquired ? 'Marcar Em Andamento' : 'Marcar Adquirido';

                const badge = button.closest('tr').querySelector('[data-item-status]');
                if (badge) {
                    badge.textContent = acquired ? 'Adquirido' : 'Em Andamento';
                    badge.className = `inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${acquired ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'}`;
                }
            } catch (error) {
                alert('Erro ao atualizar a meta.');
            }
        });
    });
});
</script>

<?= $this->endSection() ?>
