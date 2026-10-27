<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Bem-vindo, <?= esc($user['name']) ?>!</h1>
        <p class="mt-1 text-sm text-gray-500">Este e o painel inicial do MindTrack.</p>
    </div>

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
</div>
<?= $this->endSection() ?>
