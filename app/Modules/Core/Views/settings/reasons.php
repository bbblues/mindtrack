<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<style>[x-cloak]{display:none !important;}</style>

<div
    x-data='reasonsPage(<?= esc(json_encode($absenceRows, JSON_UNESCAPED_UNICODE), 'attr') ?>, <?= esc(json_encode($dischargeRows, JSON_UNESCAPED_UNICODE), 'attr') ?>)'
    @keydown.escape.window="closeModal()"
    class="space-y-6"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Motivos de Falta e Alta</h2>
            <p class="mt-1 text-sm text-gray-500">Justificativas usadas nos atendimentos e na agenda.</p>
        </div>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Novo Motivo
        </button>
    </div>

    <div x-show="pageError" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="pageError"></div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <!-- Abas -->
        <div class="flex gap-1 border-b border-gray-200 px-4 pt-3">
            <button type="button" @click="tab = 'absence'" :class="tab === 'absence' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" class="-mb-px border-b-2 px-4 py-2 text-sm font-semibold transition">
                Falta
            </button>
            <button type="button" @click="tab = 'discharge'" :class="tab === 'discharge' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" class="-mb-px border-b-2 px-4 py-2 text-sm font-semibold transition">
                Alta
            </button>
        </div>

        <!-- Falta -->
        <div x-show="tab === 'absence'" x-cloak>
            <div x-show="absences.length === 0" x-cloak class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">Nenhum motivo de falta cadastrado</h3>
                <p class="mt-1 text-sm text-gray-500">Cadastre os motivos de falta usados na agenda.</p>
                <button type="button" @click="openCreate()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Adicionar Motivo de Falta</button>
            </div>
            <div x-show="absences.length > 0" x-cloak class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Nome</th>
                            <th class="px-6 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="reason in absences" :key="reason.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-800" x-text="reason.name"></td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button" @click="openEdit(reason)" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar</button>
                                    <button type="button" @click="remove(reason)" class="ml-1 inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">Excluir</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alta -->
        <div x-show="tab === 'discharge'" x-cloak>
            <div x-show="discharges.length === 0" x-cloak class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">Nenhum motivo de alta cadastrado</h3>
                <p class="mt-1 text-sm text-gray-500">Cadastre os motivos de alta dos pacientes.</p>
                <button type="button" @click="openCreate()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Adicionar Motivo de Alta</button>
            </div>
            <div x-show="discharges.length > 0" x-cloak class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Nome</th>
                            <th class="px-6 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="reason in discharges" :key="reason.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-800" x-text="reason.name"></td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button" @click="openEdit(reason)" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar</button>
                                    <button type="button" @click="remove(reason)" class="ml-1 inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">Excluir</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal criar/editar motivo -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-30 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="closeModal()"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-800" x-text="editingId ? (tab === 'absence' ? 'Editar Motivo de Falta' : 'Editar Motivo de Alta') : (tab === 'absence' ? 'Novo Motivo de Falta' : 'Novo Motivo de Alta')"></h3>
            <form @submit.prevent="submit()" class="mt-4">
                <div>
                    <label for="reason_name" class="mb-1 block text-sm font-medium text-gray-700" x-text="tab === 'absence' ? 'Nome do Motivo de Falta *' : 'Nome do Motivo de Alta *'"></label>
                    <input type="text" id="reason_name" x-model="name" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>
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
function reasonsPage(initialAbsences, initialDischarges) {
    return {
        tab: 'absence',
        absences: initialAbsences,
        discharges: initialDischarges,
        modalOpen: false,
        editingId: null,
        name: '',
        formError: null,
        pageError: null,
        saving: false,
        csrf: <?= json_encode(csrf_hash()) ?>,

        openCreate() {
            this.editingId = null;
            this.name = '';
            this.formError = null;
            this.modalOpen = true;
        },
        openEdit(reason) {
            this.editingId = reason.id;
            this.name = reason.name;
            this.formError = null;
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
            this.editingId = null;
            this.name = '';
            this.formError = null;
        },
        listKey() {
            return this.tab === 'absence' ? 'absences' : 'discharges';
        },
        async submit() {
            this.formError = null;
            this.saving = true;

            const type = this.tab;
            const updateUrl = type === 'absence'
                ? <?= json_encode(site_url('settings/reasons/absence/update/')) ?>
                : <?= json_encode(site_url('settings/reasons/discharge/update/')) ?>;
            const storeUrl = type === 'absence'
                ? <?= json_encode(site_url('settings/reasons/absence')) ?>
                : <?= json_encode(site_url('settings/reasons/discharge')) ?>;

            const url = this.editingId ? updateUrl + this.editingId : storeUrl;

            const params = new URLSearchParams();
            params.set('name', this.name);

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
                        : (data.message || 'Não foi possível salvar o motivo.');
                    this.saving = false;
                    return;
                }

                if (data.reason) {
                    const list = this[this.listKey()];

                    if (this.editingId) {
                        const index = list.findIndex((r) => r.id === this.editingId);
                        if (index !== -1) list[index] = data.reason;
                    } else {
                        list.push(data.reason);
                        list.sort((a, b) => a.name.localeCompare(b.name));
                    }
                }

                this.closeModal();
            } catch (e) {
                this.formError = 'Falha na comunicação com o servidor.';
            }

            this.saving = false;
        },
        async remove(reason) {
            const type = this.tab;
            const label = type === 'absence' ? 'falta' : 'alta';

            if (!window.confirm(`Excluir o motivo de ${label} "${reason.name}"?`)) {
                return;
            }

            this.pageError = null;

            const url = (type === 'absence'
                ? <?= json_encode(site_url('settings/reasons/absence/delete/')) ?>
                : <?= json_encode(site_url('settings/reasons/discharge/delete/')) ?>) + reason.id;

            try {
                const res = await fetch(url, {
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
                    this.pageError = data.message || 'Não foi possível excluir o motivo.';
                    return;
                }

                this[this.listKey()] = this[this.listKey()].filter((r) => r.id !== reason.id);
            } catch (e) {
                this.pageError = 'Falha na comunicação com o servidor.';
            }
        }
    };
}
</script>

<?= $this->endSection() ?>
