<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<style>[x-cloak]{display:none !important;}</style>

<div
    x-data='insurancesPage(<?= esc(json_encode($insuranceRows, JSON_UNESCAPED_UNICODE), 'attr') ?>)'
    @keydown.escape.window="closeModal()"
    class="space-y-6"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Convênios</h2>
            <p class="mt-1 text-sm text-gray-500">Gerencie os convênios e planos de saúde.</p>
        </div>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Novo Convênio
        </button>
    </div>

    <div x-show="pageError" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="pageError"></div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div x-show="insurances.length === 0" x-cloak class="flex flex-col items-center justify-center px-6 py-16 text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Nenhum convênio cadastrado</h3>
            <p class="mt-1 text-sm text-gray-500">Cadastre o primeiro convênio da sua clínica.</p>
            <button type="button" @click="openCreate()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Adicionar Convênio</button>
        </div>

        <div x-show="insurances.length > 0" x-cloak class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Nome</th>
                        <th class="px-6 py-3">Registro ANS</th>
                        <th class="px-6 py-3">Exige Carteirinha</th>
                        <th class="px-6 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="insurance in insurances" :key="insurance.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800" x-text="insurance.name"></td>
                            <td class="px-6 py-4 text-gray-600" x-text="insurance.ans_registry || '—'"></td>
                            <td class="px-6 py-4">
                                <span x-show="insurance.requires_card === 1" x-cloak class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Sim</span>
                                <span x-show="insurance.requires_card !== 1" x-cloak class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Não</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" @click="openEdit(insurance)" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar</button>
                                <button type="button" @click="remove(insurance)" class="ml-1 inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">Excluir</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal criar/editar convênio -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-30 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="closeModal()"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-800" x-text="editingId ? 'Editar Convênio' : 'Novo Convênio'"></h3>
            <form @submit.prevent="submit()" class="mt-4">
                <div>
                    <label for="insurance_name" class="mb-1 block text-sm font-medium text-gray-700">Nome do Convênio *</label>
                    <input type="text" id="insurance_name" x-model="name" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>
                <div class="mt-4">
                    <label for="insurance_ans" class="mb-1 block text-sm font-medium text-gray-700">Registro ANS</label>
                    <input type="text" id="insurance_ans" x-model="ansRegistry" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>
                <label class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" x-model="requiresCard" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Exige carteirinha
                </label>
                <div x-show="formError" x-cloak class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" x-text="formError"></div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="closeModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</button>
                    <button type="submit" :disabled="saving" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50" x-text="saving ? 'Salvando...' : 'Salvar'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function insurancesPage(initialInsurances) {
    return {
        insurances: initialInsurances,
        modalOpen: false,
        editingId: null,
        name: '',
        ansRegistry: '',
        requiresCard: false,
        formError: null,
        pageError: null,
        saving: false,
        csrf: <?= json_encode(csrf_hash()) ?>,

        openCreate() {
            this.editingId = null;
            this.name = '';
            this.ansRegistry = '';
            this.requiresCard = false;
            this.formError = null;
            this.modalOpen = true;
        },
        openEdit(insurance) {
            this.editingId = insurance.id;
            this.name = insurance.name;
            this.ansRegistry = insurance.ans_registry || '';
            this.requiresCard = insurance.requires_card === 1;
            this.formError = null;
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
            this.editingId = null;
            this.name = '';
            this.ansRegistry = '';
            this.requiresCard = false;
            this.formError = null;
        },
        async submit() {
            this.formError = null;
            this.saving = true;

            const url = this.editingId
                ? <?= json_encode(site_url('settings/insurances/update/')) ?> + this.editingId
                : <?= json_encode(site_url('settings/insurances')) ?>;

            const params = new URLSearchParams();
            params.set('name', this.name);
            params.set('ans_registry', this.ansRegistry);
            params.set('requires_card', this.requiresCard ? '1' : '0');

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params.toString(),
                });

                const data = await res.json();

                if (data.csrf_token) {
                    this.csrf = data.csrf_token;
                }

                if (!data.success) {
                    this.formError = data.errors && Object.keys(data.errors).length
                        ? Object.values(data.errors).join(' ')
                        : (data.message || 'Não foi possível salvar o convênio.');
                    this.saving = false;
                    return;
                }

                if (data.insurance) {
                    if (this.editingId) {
                        const index = this.insurances.findIndex((i) => i.id === this.editingId);
                        if (index !== -1) this.insurances[index] = data.insurance;
                    } else {
                        this.insurances.push(data.insurance);
                        this.insurances.sort((a, b) => a.name.localeCompare(b.name));
                    }
                }

                this.closeModal();
            } catch (e) {
                this.formError = 'Falha na comunicação com o servidor.';
            }

            this.saving = false;
        },
        async remove(insurance) {
            if (!window.confirm(`Excluir o convênio "${insurance.name}"?`)) {
                return;
            }

            this.pageError = null;

            try {
                const res = await fetch(<?= json_encode(site_url('settings/insurances/delete/')) ?> + insurance.id, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(),
                });

                const data = await res.json();

                if (data.csrf_token) {
                    this.csrf = data.csrf_token;
                }

                if (!data.success) {
                    this.pageError = data.message || 'Não foi possível excluir o convênio.';
                    return;
                }

                this.insurances = this.insurances.filter((i) => i.id !== insurance.id);
            } catch (e) {
                this.pageError = 'Falha na comunicação com o servidor.';
            }
        }
    };
}
</script>

<?= $this->endSection() ?>


