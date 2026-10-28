<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$formatDate = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '—';
    }
    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('d/m/Y', $timestamp);
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Protocolos</h2>
            <p class="mt-1 text-sm text-gray-500">Catalogo de protocolos de avaliacao da sua clinica.</p>
        </div>
        <a href="<?= site_url('protocols/create') ?>" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Novo Protocolo
        </a>
    </div>

    <?php if (session('success')): ?>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= esc(session('success')) ?></div>
    <?php endif; ?>

    <?php if (session('error')): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <form method="get" action="<?= site_url('protocols') ?>" class="flex flex-wrap items-center gap-2">
        <input
            type="search"
            name="search"
            value="<?= esc($search) ?>"
            placeholder="Buscar por nome ou descricao..."
            class="min-w-[240px] flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
        >
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Buscar</button>
        <?php if ($search !== ''): ?>
            <a href="<?= site_url('protocols') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Limpar</a>
        <?php endif; ?>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <?php if ($protocols === []): ?>
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">Nenhum protocolo cadastrado</h3>
                <p class="mt-1 max-w-sm text-sm text-gray-500">Comece criando seu primeiro protocolo de avaliacao (ex: VB-MAPP, ABFW).</p>
                <a href="<?= site_url('protocols/create') ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                    + Adicionar Protocolo
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Descrição</th>
                            <th class="px-4 py-3 text-center">Níveis</th>
                            <th class="px-4 py-3 text-center">Habilidades</th>
                            <th class="px-4 py-3 text-center">Itens</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3">Situação</th>
                            <th class="px-4 py-3">Criado em</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($protocols as $protocol): ?>
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800"><?= esc($protocol['name']) ?></td>
                                <td class="max-w-xs truncate px-4 py-3 text-gray-600"><?= esc($protocol['description'] ?? '—') ?></td>
                                <td class="px-4 py-3 text-center text-gray-600"><?= (int) ($protocol['levels_count'] ?? 0) ?></td>
                                <td class="px-4 py-3 text-center text-gray-600"><?= (int) ($protocol['skills_count'] ?? 0) ?></td>
                                <td class="px-4 py-3 text-center text-gray-600"><?= (int) ($protocol['items_count'] ?? 0) ?></td>
                                <td class="px-4 py-3 text-center text-gray-600"><?= (int) ($protocol['statuses_count'] ?? 0) ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= (int) $protocol['is_active'] === 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                        <?= (int) $protocol['is_active'] === 1 ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600"><?= esc($formatDate($protocol['created_at'] ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pager->getPageCount() > 1): ?>
                <div class="border-t border-gray-200 px-4 py-3">
                    <?= $pager->links() ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
