<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<style>[x-cloak]{display:none !important;}</style>

<div
    x-data='specialtiesPage(<?= esc(json_encode($specialtyRows, JSON_UNESCAPED_UNICODE), 'attr') ?>)'
    @keydown.escape.window="closeModal()"
    class="space-y-6"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Especialidades</h2>
            <p class="mt-1 text-sm text-gray-500">Gerencie as especialidades dos profissionais.</p>
        </div>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Nova Especialidade
        </button>
    </div>

    <div x-show="pageError" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="pageError"></div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div x-show="specialties.length === 0" x-cloak class="flex flex-col items-center justify-center px-6 py-16 text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" /></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Nenhuma especialidade cadastrada</h3>
            <p class="mt-1 text-sm text-gray-500">Cadastre as especialidades atendidas pela sua clínica.</p>
            <button type="button" @click="openCreate()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Adicionar Especialidade</button>
        </div>

        <div x-show="specialties.length > 0" x-cloak class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Nome</th>
                        <th class="px-6 py-3">Data de Criação</th>
                        <th class="px-6 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="specialty in specialties" :key="specialty.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800" x-text="specialty.name"></td>
                            <td class="px-6 py-4 text-gray-600" x-text="specialty.created_at"></td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" @click="openEdit(specialty)" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar</button>
                                <button type="button" @click="remove(specialty)" class="ml-1 inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">Excluir</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal criar/editar especialidade -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-30 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="closeModal()"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-800" x-text="editingId ? 'Editar Especialidade' : 'Nova Especialidade'"></h3>
            <form @submit.prevent="submit()" class="mt-4">
                <div>
                    <label for="specialty_name" class="mb-1 block text-sm font-medium text-gray-700">Nome da Especialidade *</label>
                    <input type="text" id="specialty_name" x-model="name" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
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
function specialtiesPage(initialSpecialties) {
    return {
        specialties: initialSpecialties,
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
        openEdit(specialty) {
            this.editingId = specialty.id;
            this.name = specialty.name;
            this.formError = null;
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
            this.editingId = null;
            this.name = '';
            this.formError = null;
        },
        async submit() {
            this.formError = null;
            this.saving = true;

            const url = this.editingId
                ? <?= json_encode(site_url('settings/specialties/update/')) ?> + this.editingId
                : <?= json_encode(site_url('settings/specialties')) ?>;

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
                        : (data.message || 'Não foi possível salvar a especialidade.');
                    this.saving = false;
                    return;
                }

                if (data.specialty) {
                    if (this.editingId) {
                        const index = this.specialties.findIndex((s) => s.id === this.editingId);
                        if (index !== -1) this.specialties[index] = data.specialty;
                    } else {
                        this.specialties.push(data.specialty);
                        this.specialties.sort((a, b) => a.name.localeCompare(b.name));
                    }
                }

                this.closeModal();
            } catch (e) {
                this.formError = 'Falha na comunicação com o servidor.';
            }

            this.saving = false;
        },
        async remove(specialty) {
            if (!window.confirm(`Excluir a especialidade "${specialty.name}"?`)) {
                return;
            }

            this.pageError = null;

            try {
                const res = await fetch(<?= json_encode(site_url('settings/specialties/delete/')) ?> + specialty.id, {
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
                    this.pageError = data.message || 'Não foi possível excluir a especialidade.';
                    return;
                }

                this.specialties = this.specialties.filter((s) => s.id !== specialty.id);
            } catch (e) {
                this.pageError = 'Falha na comunicação com o servidor.';
            }
        }
    };
}
</script>

<?= $this->endSection() ?>

