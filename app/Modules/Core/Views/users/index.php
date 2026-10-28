<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Usuários</h2>
            <p class="mt-1 text-sm text-gray-500">Gerencie os usuários e cargos da sua clínica.</p>
        </div>
        <a href="<?= site_url('users/create') ?>" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Novo Usuário
        </a>
    </div>

    <?php if (session('success')): ?>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= esc(session('success')) ?></div>
    <?php endif; ?>

    <?php if (session('error')): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <form method="get" action="<?= site_url('users') ?>" class="flex flex-wrap items-center gap-2">
        <input
            type="search"
            name="search"
            value="<?= esc($search) ?>"
            placeholder="Buscar por nome ou e-mail..."
            class="min-w-[240px] flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
        >
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Buscar</button>
        <?php if ($search !== ''): ?>
            <a href="<?= site_url('users') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Limpar</a>
        <?php endif; ?>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <?php if ($users === []): ?>
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">Nenhum usuário cadastrado</h3>
                <p class="mt-1 text-sm text-gray-500">Cadastre o primeiro usuário para dar acesso à sua equipe.</p>
                <a href="<?= site_url('users/create') ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Adicionar Usuário</a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Nome</th>
                            <th class="px-6 py-3">E-mail</th>
                            <th class="px-6 py-3">Cargos</th>
                            <th class="px-6 py-3">Unidade</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($users as $user): ?>
                            <?php $userId = (int) $user['id']; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    <?= esc($user['name']) ?>
                                    <?php if ((int) ($user['is_super_admin'] ?? 0) === 1): ?>
                                        <span class="ml-2 inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-700">Super Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?= esc($user['email']) ?></td>
                                <td class="px-6 py-4">
                                    <?php $roles = $rolesByUser[$userId] ?? []; ?>
                                    <?php if ($roles === []): ?>
                                        <span class="text-gray-400">—</span>
                                    <?php else: ?>
                                        <div class="flex flex-wrap gap-1">
                                            <?php foreach ($roles as $role): ?>
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700"><?= esc($role['role_name']) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?= esc($user['unit_name'] ?? '—') ?></td>
                                <td class="px-6 py-4">
                                    <?php if ((int) ($user['is_active'] ?? 0) === 1): ?>
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Ativo</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="<?= site_url('users/edit/' . $userId) ?>" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar</a>
                                </td>
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
