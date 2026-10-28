<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Bem-vindo, <?= esc($user['name']) ?>!</h1>
        <p class="mt-1 text-sm text-gray-500">Este e o painel inicial do MindTrack.</p>
    </div>

    <?php if ($isSuperAdmin): ?>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Clínicas</h2>
                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">Tenants</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-gray-800"><?= (int) ($metrics['total_tenants'] ?? 0) ?></p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Pacientes</h2>
                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Global</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-gray-800"><?= (int) ($metrics['total_patients'] ?? 0) ?></p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Módulos</h2>
                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Catálogo</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-gray-800"><?= (int) ($metrics['total_modules'] ?? 0) ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Usuario logado</h2>
                <p class="mt-2 text-lg font-medium text-gray-800"><?= esc($user['name']) ?></p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Perfil de acesso</h2>
                <p class="mt-2 text-lg font-medium text-gray-800">
                    <?= $user['is_super_admin'] ? 'Super Admin' : 'Usuario comum' ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
