<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Gestão SaaS</h2>
            <p class="mt-1 text-sm text-gray-500">Gerencie as clínicas (tenants) e os módulos contratados.</p>
        </div>
        <a href="<?= site_url('tenants/create') ?>" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Nova Clínica
        </a>
    </div>

    <?php if (session('success')): ?>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= esc(session('success')) ?></div>
    <?php endif; ?>

    <?php if (session('error')): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <?php if ($tenants === []): ?>
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">Nenhuma clínica cadastrada</h3>
                <p class="mt-1 text-sm text-gray-500">Cadastre a primeira clínica para começar a operar o MindTrack.</p>
                <a href="<?= site_url('tenants/create') ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Adicionar Clínica</a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Nome</th>
                            <th class="px-6 py-3">Domínio</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Módulos ativos</th>
                            <th class="px-6 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($tenants as $tenant): ?>
                            <?php $tenantId = (int) $tenant['id']; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-800"><?= esc($tenant['name']) ?></td>
                                <td class="px-6 py-4 text-gray-600"><?= esc($tenant['slug'] ?? '—') ?></td>
                                <td class="px-6 py-4">
                                    <?php if ((int) ($tenant['is_active'] ?? 0) === 1): ?>
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Ativa</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">Inativa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php $activeModules = $modulesByTenant[$tenantId] ?? []; ?>
                                    <?php if ($activeModules === []): ?>
                                        <span class="text-gray-400">—</span>
                                    <?php else: ?>
                                        <div class="flex flex-wrap gap-1">
                                            <?php foreach ($activeModules as $module): ?>
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700"><?= esc($module['name']) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="<?= site_url('tenants/edit/' . $tenantId) ?>" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
