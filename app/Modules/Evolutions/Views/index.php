<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Histórico de Evoluções</h2>
            <p class="mt-1 text-sm text-gray-500"><?= esc($patient['name'] ?? 'Paciente') ?></p>
        </div>
        <a href="<?= site_url('patients/edit/' . (int) ($patient['id'] ?? 0)) ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Voltar ao Paciente</a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <?= view('App\Modules\Evolutions\Views\_feed', ['evolutions' => $evolutions]) ?>
    </div>
</div>

<?= $this->endSection() ?>
