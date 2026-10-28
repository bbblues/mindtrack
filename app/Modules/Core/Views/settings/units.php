<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<style>[x-cloak]{display:none !important;}</style>

<div
    x-data='unitsPage(<?= esc(json_encode($unitRows, JSON_UNESCAPED_UNICODE), 'attr') ?>)'
    @keydown.escape.window="closeModal()"
    class="space-y-6"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Unidades</h2>
            <p class="mt-1 text-sm text-gray-500">Gerencie as unidades da sua clínica.</p>
        </div>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Nova Unidade
        </button>
    </div>

    <div x-show="pageError" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="pageError"></div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div x-show="units.length === 0" x-cloak class="flex flex-col items-center justify-center px-6 py-16 text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Nenhuma unidade cadastrada</h3>
            <p class="mt-1 text-sm text-gray-500">Cadastre a primeira unidade da sua clínica.</p>
            <button type="button" @click="openCreate()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Adicionar Unidade</button>
        </div>

        <div x-show="units.length > 0" x-cloak class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Nome</th>
                        <th class="px-6 py-3">CNPJ</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="unit in units" :key="unit.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800" x-text="unit.name"></td>
                            <td class="px-6 py-4 text-gray-600" x-text="unit.cnpj || '—'"></td>
                            <td class="px-6 py-4">
                                <span x-show="unit.is_active === 1" x-cloak class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Ativa</span>
                                <span x-show="unit.is_active !== 1" x-cloak class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">Inativa</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" @click="openEdit(unit)" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar</button>
                                <button type="button" @click="remove(unit)" class="ml-1 inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">Excluir</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal criar/editar unidade -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-30 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="closeModal()"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-800" x-text="editingId ? 'Editar Unidade' : 'Nova Unidade'"></h3>
            <form @submit.prevent="submit()" class="mt-4">
                <div>
                    <label for="unit_name" class="mb-1 block text-sm font-medium text-gray-700">Nome da Unidade *</label>
                    <input type="text" id="unit_name" x-model="name" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>
                <div class="mt-4">
                    <label for="unit_cnpj" class="mb-1 block text-sm font-medium text-gray-700">CNPJ</label>
                    <input type="text" id="unit_cnpj" x-model="cnpj" maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>
                <label class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" x-model="isActive" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Unidade ativa
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
function unitsPage(initialUnits) {
    return {
        units: initialUnits,
        modalOpen: false,
        editingId: null,
        name: '',
        cnpj: '',
        isActive: true,
        formError: null,
        pageError: null,
        saving: false,
        csrf: <?= json_encode(csrf_token()) ?>,

        openCreate() {
            this.editingId = null;
            this.name = '';
            this.cnpj = '';
            this.isActive = true;
            this.formError = null;
            this.modalOpen = true;
        },
        openEdit(unit) {
            this.editingId = unit.id;
            this.name = unit.name;
            this.cnpj = unit.cnpj || '';
            this.isActive = unit.is_active === 1;
            this.formError = null;
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
            this.editingId = null;
            this.name = '';
            this.cnpj = '';
            this.isActive = true;
            this.formError = null;
        },
        async submit() {
            this.formError = null;
            this.saving = true;

            const url = this.editingId
                ? <?= json_encode(site_url('settings/units/update/')) ?> + this.editingId
                : <?= json_encode(site_url('settings/units')) ?>;

            const params = new URLSearchParams();
            params.set('name', this.name);
            params.set('cnpj', this.cnpj);
            params.set('is_active', this.isActive ? '1' : '0');

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
                        : (data.message || 'Não foi possível salvar a unidade.');
                    this.saving = false;
                    return;
                }

                if (data.unit) {
                    if (this.editingId) {
                        const index = this.units.findIndex((u) => u.id === this.editingId);
                        if (index !== -1) this.units[index] = data.unit;
                    } else {
                        this.units.push(data.unit);
                        this.units.sort((a, b) => a.name.localeCompare(b.name));
                    }
                }

                this.closeModal();
            } catch (e) {
                this.formError = 'Falha na comunicação com o servidor.';
            }

            this.saving = false;
        },
        async remove(unit) {
            if (!window.confirm(`Excluir a unidade "${unit.name}"?`)) {
                return;
            }

            this.pageError = null;

            try {
                const res = await fetch(<?= json_encode(site_url('settings/units/delete/')) ?> + unit.id, {
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
                    this.pageError = data.message || 'Não foi possível excluir a unidade.';
                    return;
                }

                this.units = this.units.filter((u) => u.id !== unit.id);
            } catch (e) {
                this.pageError = 'Falha na comunicação com o servidor.';
            }
        }
    };
}
</script>

<?= $this->endSection() ?>


