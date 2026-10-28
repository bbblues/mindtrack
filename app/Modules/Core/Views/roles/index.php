<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<style>[x-cloak]{display:none !important;}</style>

<div
    x-data='rolesPage(<?= esc(json_encode($roleRows, JSON_UNESCAPED_UNICODE), 'attr') ?>)'
    @keydown.escape.window="closeModal()"
    class="space-y-6"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Cargos e Papéis</h2>
            <p class="mt-1 text-sm text-gray-500">Gerencie os cargos da sua clínica. Os cargos padrão não podem ser excluídos.</p>
        </div>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Novo Cargo
        </button>
    </div>

    <div x-show="pageError" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="pageError"></div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div x-show="roles.length === 0" x-cloak class="flex flex-col items-center justify-center px-6 py-16 text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Nenhum cargo cadastrado</h3>
            <p class="mt-1 text-sm text-gray-500">Crie o primeiro cargo personalizado para a sua clínica.</p>
            <button type="button" @click="openCreate()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Adicionar Cargo</button>
        </div>

        <div x-show="roles.length > 0" x-cloak class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Nome</th>
                        <th class="px-6 py-3">Data de Criação</th>
                        <th class="px-6 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="role in roles" :key="role.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">
                                <span x-text="role.name"></span>
                                <span x-show="role.is_default" x-cloak class="ml-2 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">Padrão</span>
                            </td>
                            <td class="px-6 py-4 text-gray-600" x-text="role.created_at"></td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" @click="openEdit(role)" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar</button>
                                <button type="button" x-show="!role.is_default" @click="remove(role)" class="ml-1 inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">Excluir</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal criar/editar cargo -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-30 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="closeModal()"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-800" x-text="editingId ? 'Editar Cargo' : 'Novo Cargo'"></h3>
            <form @submit.prevent="submit()" class="mt-4">
                <div>
                    <label for="role_name" class="mb-1 block text-sm font-medium text-gray-700">Nome do Cargo *</label>
                    <input type="text" id="role_name" x-model="name" required maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
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
function rolesPage(initialRoles) {
    return {
        roles: initialRoles,
        modalOpen: false,
        editingId: null,
        name: '',
        formError: null,
        pageError: null,
        saving: false,
        csrf: <?= json_encode(csrf_token()) ?>,

        openCreate() {
            this.editingId = null;
            this.name = '';
            this.formError = null;
            this.modalOpen = true;
        },
        openEdit(role) {
            this.editingId = role.id;
            this.name = role.name;
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
                ? <?= json_encode(site_url('roles/update/')) ?> + this.editingId
                : <?= json_encode(site_url('roles')) ?>;

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
                        : (data.message || 'Não foi possível salvar o cargo.');
                    this.saving = false;
                    return;
                }

                if (data.role) {
                    if (this.editingId) {
                        const index = this.roles.findIndex((r) => r.id === this.editingId);
                        if (index !== -1) this.roles[index] = data.role;
                    } else {
                        this.roles.push(data.role);
                        this.roles.sort((a, b) => a.name.localeCompare(b.name));
                    }
                }

                this.closeModal();
            } catch (e) {
                this.formError = 'Falha na comunicação com o servidor.';
            }

            this.saving = false;
        },
        async remove(role) {
            if (!window.confirm(`Excluir o cargo "${role.name}"?`)) {
                return;
            }

            this.pageError = null;

            try {
                const res = await fetch(<?= json_encode(site_url('roles/delete/')) ?> + role.id, {
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
                    this.pageError = data.message || 'Não foi possível excluir o cargo.';
                    return;
                }

                this.roles = this.roles.filter((r) => r.id !== role.id);
            } catch (e) {
                this.pageError = 'Falha na comunicação com o servidor.';
            }
        }
    };
}
</script>

<?= $this->endSection() ?>
