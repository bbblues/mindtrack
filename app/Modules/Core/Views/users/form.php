<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$isEdit = $user !== null;
$u = $user ?? [];
$formAction = $isEdit ? site_url($basePath . '/update/' . (int) $u['id']) : site_url($basePath);
$selectedUnitId = (string) old('unit_id', $u['unit_id'] ?? '');
$selectedTenantId = (string) old('tenant_id', $u['tenant_id'] ?? '');
$selectedRoleIds = array_map('intval', $selectedRoleIds);
?>

<div class="mx-auto max-w-4xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800"><?= esc($title) ?></h2>
            <p class="mt-1 text-sm text-gray-500">Preencha os dados do usuário e selecione os cargos.</p>
        </div>
        <a href="<?= site_url($basePath) ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Voltar</a>
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
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nome *</label>
                    <input type="text" id="name" name="name" value="<?= esc(old('name', $u['name'] ?? '')) ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-700">E-mail *</label>
                    <input type="email" id="email" name="email" value="<?= esc(old('email', $u['email'] ?? '')) ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Senha <?= $isEdit ? '' : '*' ?></label>
                    <input type="password" id="password" name="password" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?> class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    <?php if ($isEdit): ?>
                        <p class="mt-1 text-xs text-gray-500">Deixe em branco para manter a senha atual.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="unit_id" class="mb-1 block text-sm font-medium text-gray-700">Unidade</label>
                    <select id="unit_id" name="unit_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        <option value="">Selecione...</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?= (int) $unit['id'] ?>" <?= $selectedUnitId === (string) (int) $unit['id'] ? 'selected' : '' ?>><?= esc($unit['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($isSuperAdmin): ?>
                    <div>
                        <label for="tenant_id" class="mb-1 block text-sm font-medium text-gray-700">Clínica (tenant)</label>
                        <select id="tenant_id" name="tenant_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            <option value="">Global (sem clínica)</option>
                            <?php foreach ($tenants as $tenant): ?>
                                <option value="<?= (int) $tenant['id'] ?>" <?= $selectedTenantId === (string) (int) $tenant['id'] ? 'selected' : '' ?>><?= esc($tenant['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="flex flex-wrap items-end gap-6 pb-1">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?= ((int) old('is_active', $u['is_active'] ?? 1)) === 1 ? 'checked' : '' ?>>
                        Usuário ativo
                    </label>

                    <?php if ($isSuperAdmin): ?>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="hidden" name="is_super_admin" value="0">
                            <input type="checkbox" name="is_super_admin" value="1" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?= ((int) old('is_super_admin', $u['is_super_admin'] ?? 0)) === 1 ? 'checked' : '' ?>>
                            Super Admin
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700">Cargos (Roles)</h3>
                <p class="mt-1 text-xs text-gray-500">Selecione um ou mais cargos para este usuário.</p>

                <?php if ($roles === []): ?>
                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                        Nenhum cargo cadastrado. Rode o seeder de cargos antes de criar usuários.
                    </div>
                <?php else: ?>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <?php foreach ($roles as $role): ?>
                            <?php $roleId = (int) $role['id']; ?>
                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 transition hover:border-blue-300">
                                <input type="checkbox" name="roles[]" value="<?= $roleId ?>" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?= in_array($roleId, $selectedRoleIds, true) ? 'checked' : '' ?>>
                                <span>
                                    <span class="block text-sm font-medium text-gray-800"><?= esc($role['name']) ?></span>
                                    <?php if (! empty($role['description'])): ?>
                                        <span class="block text-xs text-gray-500"><?= esc($role['description']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
            <a href="<?= site_url($basePath) ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Salvar</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
