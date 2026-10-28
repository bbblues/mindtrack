<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$isEdit = $tenant !== null;
$t = $tenant ?? [];
$formAction = $isEdit ? site_url('tenants/update/' . (int) $t['id']) : site_url('tenants');
$selectedIds = array_map('intval', old('modules', $selectedModules ?? []) ?? []);
?>

<div class="mx-auto max-w-4xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800"><?= esc($title) ?></h2>
            <p class="mt-1 text-sm text-gray-500">Preencha os dados da clínica e selecione os módulos contratados.</p>
        </div>
        <a href="<?= site_url('tenants') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Voltar</a>
    </div>

    <?php $validationErrors = session('errors'); ?>
    <?php if (is_array($validationErrors) && $validationErrors !== []): ?>
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-inside list-disc space-y-1">
                <?php foreach ($validationErrors as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= $formAction ?>" method="post" class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <?= csrf_field() ?>

        <div class="space-y-6 p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nome da clínica *</label>
                    <input type="text" id="name" name="name" value="<?= esc(old('name', $t['name'] ?? '')) ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>

                <div>
                    <label for="slug" class="mb-1 block text-sm font-medium text-gray-700">Domínio (slug)</label>
                    <input type="text" id="slug" name="slug" value="<?= esc(old('slug', $t['slug'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    <p class="mt-1 text-xs text-gray-500">Identificador único da clínica (ex: clinica-exemplo).</p>
                </div>

                <div>
                    <label for="cnpj" class="mb-1 block text-sm font-medium text-gray-700">CNPJ</label>
                    <input type="text" id="cnpj" name="cnpj" value="<?= esc(old('cnpj', $t['cnpj'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= esc(old('email', $t['email'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>

                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="text" id="phone" name="phone" value="<?= esc(old('phone', $t['phone'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>

                <div class="flex items-end pb-1">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?= ((int) old('is_active', $t['is_active'] ?? 1)) === 1 ? 'checked' : '' ?>>
                        Clínica ativa
                    </label>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700">Módulos Disponíveis</h3>
                <p class="mt-1 text-xs text-gray-500">Os módulos base (Core, Pacientes e Agenda) são ativados automaticamente.</p>

                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <?php foreach ($modules as $module): ?>
                        <?php $moduleId = (int) $module['id']; ?>
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 transition hover:border-blue-300">
                            <input type="checkbox" name="modules[]" value="<?= $moduleId ?>" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?= in_array($moduleId, $selectedIds, true) ? 'checked' : '' ?>>
                            <span>
                                <span class="block text-sm font-medium text-gray-800"><?= esc($module['name']) ?></span>
                                <?php if (! empty($module['description'])): ?>
                                    <span class="block text-xs text-gray-500"><?= esc($module['description']) ?></span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
            <a href="<?= site_url('tenants') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Salvar</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
