<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$payload = [
    'roles'             => $roles,
    'modules'           => $modules,
    'rolePermissionIds' => $rolePermissionIds,
    'selectedRoleId'    => $selectedRoleId,
    'csrfToken'         => csrf_hash(),
    'updateUrl'         => site_url($basePath . '/permissions'),
    'listUrl'           => site_url($basePath),
];
?>

<style>[x-cloak]{display:none !important;}</style>

<div x-data='permissionsPage(<?= esc(json_encode($payload, JSON_UNESCAPED_UNICODE), 'attr') ?>)' x-cloak class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Cargos e Permissões</h2>
            <p class="mt-1 text-sm text-gray-500">Selecione um cargo e defina quais ações cada papel pode executar na clínica.</p>
        </div>
        <a :href="listUrl" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Voltar</a>
    </div>

    <div x-show="error" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="error"></div>

    <div x-show="isAdmin" x-cloak class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700">
        Este cargo possui <strong>acesso total</strong> e não pode ser editado.
    </div>

    <!-- Abas de cargos -->
    <div class="flex flex-wrap gap-2">
        <template x-for="role in roles" :key="role.id">
            <button type="button" @click="switchRole(role)"
                :class="role.id === activeRoleId ? 'bg-blue-600 text-white shadow-sm' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition">
                <span x-text="role.name"></span>
                <span x-show="role.slug === 'admin'" x-cloak class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-semibold">Acesso Total</span>
            </button>
        </template>
    </div>

    <!-- Accordion por módulo -->
    <div class="space-y-3">
        <template x-for="module in modules" :key="module.slug">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <button type="button" @click="toggleModule(module.slug)" class="flex w-full items-center justify-between px-5 py-4 text-left transition hover:bg-gray-50">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-800" x-text="module.label"></span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500" x-text="moduleSummary(module)"></span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 transition" :class="isOpen(module.slug) ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="isOpen(module.slug)" x-cloak class="border-t border-gray-100 px-5 py-4">
                    <div class="space-y-3">
                        <template x-for="action in module.actions" :key="action.type === 'single' ? action.slug : action.base">
                            <div>
                                <!-- Ação sem escopo -->
                                <div x-show="action.type === 'single'" class="flex items-center justify-between gap-4">
                                    <span class="text-sm text-gray-700" x-text="action.label"></span>
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input type="checkbox"
                                            :checked="isChecked(action.permissionId)"
                                            @change="toggle(action.permissionId)"
                                            :disabled="isAdmin"
                                            title="Permitir esta ação"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-50">
                                    </label>
                                </div>

                                <!-- Ação com escopo any/own -->
                                <div x-show="action.type === 'scoped'" class="flex flex-wrap items-center justify-between gap-4">
                                    <span class="text-sm text-gray-700" x-text="action.label"></span>
                                    <div class="flex items-center gap-5">
                                        <template x-if="action.any">
                                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                                                <input type="checkbox"
                                                    :checked="isChecked(action.any.permissionId)"
                                                    @change="toggle(action.any.permissionId)"
                                                    :disabled="isAdmin"
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-50">
                                                <span>Todos</span>
                                            </label>
                                        </template>
                                        <template x-if="action.own">
                                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                                                <input type="checkbox"
                                                    :checked="isChecked(action.own.permissionId)"
                                                    @change="toggle(action.own.permissionId)"
                                                    :disabled="isAdmin"
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-50">
                                                <span>Somente os próprios</span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Barra de salvar -->
    <div class="sticky bottom-4 flex items-center justify-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-lg">
        <span x-show="saved" x-cloak class="text-sm font-medium text-green-600">Permissões salvas ✓</span>
        <button type="button" @click="save()" :disabled="isAdmin || saving"
            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50"
            x-text="saving ? 'Salvando...' : 'Salvar alterações'"></button>
    </div>
</div>

<script>
function permissionsPage(payload) {
    return {
        roles: payload.roles,
        modules: payload.modules,
        rolePerms: payload.rolePermissionIds,
        activeRoleId: payload.selectedRoleId,
        csrf: payload.csrfToken,
        updateUrl: payload.updateUrl,
        listUrl: payload.listUrl,
        draft: [],
        openModules: {},
        saving: false,
        saved: false,
        error: '',

        get activeRole() {
            return this.roles.find((r) => r.id === this.activeRoleId) || this.roles[0] || null;
        },
        get isAdmin() {
            return this.activeRole !== null && this.activeRole.slug === 'admin';
        },

        init() {
            this.modules.forEach((m) => { this.openModules[m.slug] = true; });
            this.syncDraft();
        },

        syncDraft() {
            this.draft = [...(this.rolePerms[this.activeRoleId] || [])];
        },

        switchRole(role) {
            this.activeRoleId = role.id;
            this.syncDraft();
            this.saved = false;
            this.error = '';
        },

        toggleModule(slug) {
            this.openModules[slug] = !this.openModules[slug];
        },

        isOpen(slug) {
            return this.openModules[slug] !== false;
        },

        isChecked(permissionId) {
            return this.draft.includes(permissionId);
        },

        toggle(permissionId) {
            if (this.isAdmin) return;
            const i = this.draft.indexOf(permissionId);
            if (i >= 0) this.draft.splice(i, 1);
            else this.draft.push(permissionId);
            this.saved = false;
        },

        moduleSummary(module) {
            const ids = [];
            module.actions.forEach((action) => {
                if (action.type === 'single') {
                    if (action.permissionId) ids.push(action.permissionId);
                } else {
                    if (action.any && action.any.permissionId) ids.push(action.any.permissionId);
                    if (action.own && action.own.permissionId) ids.push(action.own.permissionId);
                }
            });
            const checked = ids.filter((id) => this.draft.includes(id)).length;
            return checked + '/' + ids.length;
        },

        async save() {
            if (this.isAdmin) return;
            this.saving = true;
            this.saved = false;
            this.error = '';

            const params = new URLSearchParams();
            this.draft.forEach((id) => params.append('permission_ids[]', id));

            try {
                const res = await fetch(this.updateUrl + '/' + this.activeRoleId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params.toString(),
                });

                const data = await res.json();

                if (data.csrf_token) this.csrf = data.csrf_token;

                if (!data.success) {
                    this.error = data.message || 'Não foi possível salvar as permissões.';
                    this.saving = false;
                    return;
                }

                this.rolePerms[this.activeRoleId] = [...this.draft];
                this.saved = true;
                setTimeout(() => { this.saved = false; }, 2500);
            } catch (e) {
                this.error = 'Falha na comunicação com o servidor.';
            }

            this.saving = false;
        },
    };
}
</script>

<?= $this->endSection() ?>
