<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Meu Perfil</h2>
        <p class="mt-1 text-sm text-gray-500">Atualize seus dados pessoais e, se quiser, a sua senha de acesso.</p>
    </div>

    <?php if (session('success')): ?>
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= esc(session('success')) ?></div>
    <?php endif; ?>

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

    <form action="<?= site_url('profile') ?>" method="post" class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <?= csrf_field() ?>

        <div class="space-y-6 p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nome *</label>
                    <input type="text" id="name" name="name" value="<?= esc(old('name', $user['name'] ?? '')) ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-700">E-mail *</label>
                    <input type="email" id="email" name="email" value="<?= esc(old('email', $user['email'] ?? '')) ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <h3 class="text-sm font-semibold text-gray-700">Alterar senha</h3>
                <p class="mt-1 text-xs text-gray-500">Informe a senha atual para confirmar qualquer alteração. Deixe o campo de nova senha em branco para manter a senha atual.</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="current_password" class="mb-1 block text-sm font-medium text-gray-700">Senha atual *</label>
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Nova senha</label>
                        <input type="password" id="password" name="password" autocomplete="new-password" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>

                    <div>
                        <label for="password_confirm" class="mb-1 block text-sm font-medium text-gray-700">Confirmar nova senha</label>
                        <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
            <a href="<?= site_url('dashboard') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Salvar alterações</button>
        </div>
    </form>
</div>

<?= $this->endSection() ?>
