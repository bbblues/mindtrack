<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<style>[x-cloak]{display:none !important;}</style>

<div
    x-data='tagsPage(<?= esc(json_encode($tagRows, JSON_UNESCAPED_UNICODE), 'attr') ?>)'
    @keydown.escape.window="closeModal()"
    class="space-y-6"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Tags</h2>
            <p class="mt-1 text-sm text-gray-500">Etiquetas coloridas para organizar pacientes e atendimentos.</p>
        </div>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Nova Tag
        </button>
    </div>

    <div x-show="pageError" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="pageError"></div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div x-show="tags.length === 0" x-cloak class="flex flex-col items-center justify-center px-6 py-16 text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Nenhuma tag cadastrada</h3>
            <p class="mt-1 text-sm text-gray-500">Crie tags coloridas para organizar sua rotina.</p>
            <button type="button" @click="openCreate()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Adicionar Tag</button>
        </div>
        <div x-show="tags.length > 0" x-cloak class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Nome</th>
                        <th class="px-6 py-3">Cor</th>
                        <th class="px-6 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="tag in tags" :key="tag.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800" x-text="tag.name"></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-4 w-4 rounded-full border border-gray-200" :style="'background-color:' + tag.color"></span>
                                    <span class="font-mono text-xs text-gray-600" x-text="tag.color"></span>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" @click="openEdit(tag)" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar</button>
                                <button type="button" @click="remove(tag)" class="ml-1 inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">Excluir</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal criar/editar tag -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-30 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="closeModal()"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-800" x-text="editingId ? 'Editar Tag' : 'Nova Tag'"></h3>
            <form @submit.prevent="submit()" class="mt-4">
                <div>
                    <label for="tag_name" class="mb-1 block text-sm font-medium text-gray-700">Nome da Tag *</label>
                    <input type="text" id="tag_name" x-model="name" required maxlength="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>
                <div class="mt-4">
                    <label for="tag_color" class="mb-1 block text-sm font-medium text-gray-700">Cor *</label>
                    <div class="flex items-center gap-3">
                        <input type="color" id="tag_color" x-model="color" required class="h-10 w-16 cursor-pointer rounded-lg border border-gray-300 bg-white p-1 shadow-sm">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-white" :style="'background-color:' + color" x-text="color"></span>
                    </div>
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
function tagsPage(initialTags) {
    return {
        tags: initialTags,
        modalOpen: false,
        editingId: null,
        name: '',
        color: '#3B82F6',
        formError: null,
        pageError: null,
        saving: false,
        csrf: <?= json_encode(csrf_hash()) ?>,

        openCreate() {
            this.editingId = null;
            this.name = '';
            this.color = '#3B82F6';
            this.formError = null;
            this.modalOpen = true;
        },
        openEdit(tag) {
            this.editingId = tag.id;
            this.name = tag.name;
            this.color = tag.color;
            this.formError = null;
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
            this.editingId = null;
            this.name = '';
            this.color = '#3B82F6';
            this.formError = null;
        },
        async submit() {
            this.formError = null;
            this.saving = true;

            const url = this.editingId
                ? <?= json_encode(site_url('settings/tags/update/')) ?> + this.editingId
                : <?= json_encode(site_url('settings/tags')) ?>;

            const params = new URLSearchParams();
            params.set('name', this.name);
            params.set('color', this.color);

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
                        : (data.message || 'Não foi possível salvar a tag.');
                    this.saving = false;
                    return;
                }

                if (data.tag) {
                    if (this.editingId) {
                        const index = this.tags.findIndex((t) => t.id === this.editingId);
                        if (index !== -1) this.tags[index] = data.tag;
                    } else {
                        this.tags.push(data.tag);
                        this.tags.sort((a, b) => a.name.localeCompare(b.name));
                    }
                }

                this.closeModal();
            } catch (e) {
                this.formError = 'Falha na comunicação com o servidor.';
            }

            this.saving = false;
        },
        async remove(tag) {
            if (!window.confirm(`Excluir a tag "${tag.name}"?`)) {
                return;
            }

            this.pageError = null;

            try {
                const res = await fetch(<?= json_encode(site_url('settings/tags/delete/')) ?> + tag.id, {
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
                    this.pageError = data.message || 'Não foi possível excluir a tag.';
                    return;
                }

                this.tags = this.tags.filter((t) => t.id !== tag.id);
            } catch (e) {
                this.pageError = 'Falha na comunicação com o servidor.';
            }
        }
    };
}
</script>

<?= $this->endSection() ?>
