<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$maskCpf = static function (?string $cpf): string {
    if ($cpf === null || $cpf === '') {
        return '—';
    }
    $digits = preg_replace('/\D/', '', $cpf);
    if (strlen($digits) !== 11) {
        return $cpf;
    }

    return vsprintf('%s.%s.%s-%s', str_split($digits, 3));
};

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
            <h2 class="text-xl font-semibold text-gray-800">Pacientes</h2>
            <p class="mt-1 text-sm text-gray-500">Gerencie os pacientes da sua clinica.</p>
        </div>
        <a href="<?= site_url('patients/create') ?>" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Novo Paciente
        </a>
    </div>

    <?php if (session('success')): ?>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= esc(session('success')) ?></div>
    <?php endif; ?>

    <?php if (session('error')): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <form method="get" action="<?= site_url('patients') ?>" class="flex flex-wrap items-center gap-2">
        <input
            type="search"
            name="search"
            value="<?= esc($search) ?>"
            placeholder="Buscar por nome ou CPF..."
            class="min-w-[240px] flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
        >
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Buscar</button>
        <?php if ($search !== ''): ?>
            <a href="<?= site_url('patients') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Limpar</a>
        <?php endif; ?>
    </form>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <?php if ($patients === []): ?>
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">Nenhum paciente encontrado</h3>
                <p class="mt-1 text-sm text-gray-500">Comece cadastrando o primeiro paciente da sua clinica.</p>
                <a href="<?= site_url('patients/create') ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Adicionar Paciente</a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">CPF</th>
                            <th class="px-4 py-3">Nascimento</th>
                            <th class="px-4 py-3">Unidade</th>
                            <th class="px-4 py-3">Convênio</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($patients as $patient): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800"><?= esc($patient['name']) ?></td>
                                <td class="px-4 py-3 text-gray-600"><?= esc($maskCpf($patient['cpf'] ?? null)) ?></td>
                                <td class="px-4 py-3 text-gray-600"><?= esc($formatDate($patient['birth_date'] ?? null)) ?></td>
                                <td class="px-4 py-3 text-gray-600"><?= esc($patient['unit_name'] ?? '—') ?></td>
                                <td class="px-4 py-3 text-gray-600"><?= esc($patient['insurance_name'] ?? '—') ?></td>
                                <td class="px-4 py-3">
                                    <button
                                        type="button"
                                        data-toggle-status
                                        data-patient-id="<?= (int) $patient['id'] ?>"
                                        data-status="<?= (int) $patient['is_active'] ?>"
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold transition <?= (int) $patient['is_active'] === 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"
                                    >
                                        <?= (int) $patient['is_active'] === 1 ? 'Ativo' : 'Inativo' ?>
                                    </button>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="<?= site_url('patients/edit/' . (int) $patient['id']) ?>" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar</a>
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
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const token = <?= json_encode(csrf_token()) ?>;
        const baseUrl = <?= json_encode(site_url('patients/toggle-status')) ?>;

        document.querySelectorAll('[data-toggle-status]').forEach((button) => {
            button.addEventListener('click', async () => {
                const id = button.dataset.patientId;

                try {
                    const response = await fetch(`${baseUrl}/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({}),
                    });

                    const data = await response.json();

                    if (! data.success) {
                        alert(data.message || 'Erro ao atualizar o status.');
                        return;
                    }

                    const isActive = data.is_active === 1;
                    button.dataset.status = data.is_active;
                    button.textContent = isActive ? 'Ativo' : 'Inativo';
                    button.className = `inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold transition ${isActive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
                } catch (error) {
                    alert('Erro ao atualizar o status.');
                }
            });
        });
    });
    </script>


</div>
<?= $this->endSection() ?>
