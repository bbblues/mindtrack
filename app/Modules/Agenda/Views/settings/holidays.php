<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<style>[x-cloak]{display:none !important;}</style>

<?php
$citiesJson = array_map(static fn (array $city): array => [
    'id'        => (int) $city['id'],
    'city_name' => (string) ($city['city_name'] ?? ''),
    'state'     => (string) ($city['state'] ?? ''),
    'city_code' => (string) ($city['city_code'] ?? ''),
], $cities);

$holidayJson = static fn (array $holiday): array => [
    'id'    => (int) $holiday['id'],
    'name'  => (string) ($holiday['name'] ?? ''),
    'date'  => (string) ($holiday['date'] ?? ''),
    'scope' => (string) ($holiday['scope'] ?? ''),
    'badge' => (string) ($holiday['badge'] ?? ''),
    'color' => (string) ($holiday['color'] ?? '#f59e0b'),
];

$nationalJson = array_map($holidayJson, $national);
$manualJson   = array_map($holidayJson, $manual);
?>

<div
    x-data='holidaysPage(<?= esc(json_encode($citiesJson, JSON_UNESCAPED_UNICODE), 'attr') ?>, <?= esc(json_encode($nationalJson, JSON_UNESCAPED_UNICODE), 'attr') ?>, <?= esc(json_encode($manualJson, JSON_UNESCAPED_UNICODE), 'attr') ?>, <?= esc(json_encode($badges, JSON_UNESCAPED_UNICODE), 'attr') ?>, <?= esc(json_encode($manualScopes, JSON_UNESCAPED_UNICODE), 'attr') ?>, <?= esc(json_encode($states, JSON_UNESCAPED_UNICODE), 'attr') ?>)'
    @keydown.escape.window="closeAll()"
    class="space-y-6"
>
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Feriados e Cidade</h2>
        <p class="mt-1 text-sm text-gray-500">Cadastre a cidade da clínica, sincronize os feriados nacionais e registre feriados locais ou datas de fechamento.</p>
    </div>

    <div x-show="pageError" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="pageError"></div>
    <div x-show="successMessage" x-cloak class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700" x-text="successMessage"></div>

    <!-- Cidade -->
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-gray-800">Cidade</h3>
                <p class="text-sm text-gray-500">Usada para identificar feriados municipais/estaduais.</p>
            </div>
            <button type="button" @click="openCityModal()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Cidade</button>
        </div>

        <div x-show="cities.length === 0" x-cloak class="rounded-lg bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">Nenhuma cidade cadastrada.</div>
        <ul x-show="cities.length > 0" x-cloak class="divide-y divide-gray-100">
            <template x-for="city in cities" :key="city.id">
                <li class="flex items-center justify-between gap-3 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-800" x-text="city.city_name + ' - ' + city.state"></p>
                        <p class="text-xs text-gray-500" x-text="city.city_code ? 'IBGE: ' + city.city_code : 'Sem código IBGE'"></p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="openCityModal(city)" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar</button>
                        <button type="button" @click="removeCity(city)" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">Excluir</button>
                    </div>
                </li>
            </template>
        </ul>
    </div>

    <!-- Feriados Nacionais -->
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-gray-800">Feriados Nacionais</h3>
                <p class="text-sm text-gray-500">Sincronizados automaticamente com a BrasilAPI.</p>
            </div>
            <button type="button" @click="syncNational()" :disabled="syncing" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">
                <span x-show="syncing" x-cloak>Sincronizando…</span>
                <span x-show="!syncing">Sincronizar agora</span>
            </button>
        </div>

        <div x-show="national.length === 0" x-cloak class="rounded-lg bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">Nenhum feriado nacional sincronizado. Clique em "Sincronizar agora".</div>
        <ul x-show="national.length > 0" x-cloak class="divide-y divide-gray-100">
            <template x-for="holiday in national" :key="holiday.id">
                <li class="flex items-center justify-between gap-3 py-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold" :style="'background-color:' + holiday.color + '22;color:' + holiday.color" x-text="holiday.badge"></span>
                        <div>
                            <p class="text-sm font-medium text-gray-800" x-text="holiday.name"></p>
                            <p class="text-xs text-gray-500" x-text="formatDate(holiday.date)"></p>
                        </div>
                    </div>
                </li>
            </template>
        </ul>
    </div>

    <!-- Feriados locais e fechamentos -->
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-gray-800">Feriados locais e fechamentos</h3>
                <p class="text-sm text-gray-500">Datas específicas da sua clínica ou município.</p>
            </div>
            <button type="button" @click="openManualModal()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Feriado local</button>
        </div>

        <div x-show="manual.length === 0" x-cloak class="rounded-lg bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">Nenhum feriado local cadastrado.</div>
        <ul x-show="manual.length > 0" x-cloak class="divide-y divide-gray-100">
            <template x-for="holiday in manual" :key="holiday.id">
                <li class="flex items-center justify-between gap-3 py-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold" :style="'background-color:' + holiday.color + '22;color:' + holiday.color" x-text="holiday.badge"></span>
                        <div>
                            <p class="text-sm font-medium text-gray-800" x-text="holiday.name"></p>
                            <p class="text-xs text-gray-500" x-text="formatDate(holiday.date)"></p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="openManualModal(holiday)" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar</button>
                        <button type="button" @click="removeManual(holiday)" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">Excluir</button>
                    </div>
                </li>
            </template>
        </ul>
    </div>

    <!-- Modal: Cidade -->
    <div x-show="cityModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.outside="closeCityModal()">
            <h3 class="text-lg font-semibold text-gray-800" x-text="cityModal.id ? 'Editar cidade' : 'Nova cidade'"></h3>
            <form @submit.prevent="submitCity()" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nome da cidade *</label>
                    <input type="text" x-model="cityModal.city_name" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Estado (UF) *</label>
                    <select x-model="cityModal.state" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        <option value="">Selecione...</option>
                        <template x-for="(name, uf) in states" :key="uf">
                            <option :value="uf" x-text="uf + ' - ' + name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Código IBGE (opcional)</label>
                    <input type="text" x-model="cityModal.city_code" maxlength="7" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>
                <p x-show="cityModal.error" x-cloak class="text-sm text-red-600" x-text="cityModal.error"></p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="closeCityModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</button>
                    <button type="submit" :disabled="cityModal.saving" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">Salvar</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Modal: Feriado local -->
    <div x-show="manualModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.outside="closeManualModal()">
            <h3 class="text-lg font-semibold text-gray-800" x-text="manualModal.id ? 'Editar feriado' : 'Novo feriado local'"></h3>
            <form @submit.prevent="submitManual()" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Data *</label>
                    <input type="date" x-model="manualModal.date" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nome *</label>
                    <input type="text" x-model="manualModal.name" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Badge *</label>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <template x-for="scope in manualScopes" :key="scope">
                            <button type="button" @click="manualModal.scope = scope" :class="manualModal.scope === scope ? 'ring-2 ring-blue-500 border-blue-500' : 'border-gray-300'" class="flex items-center gap-2 rounded-lg border px-3 py-2 text-left transition">
                                <span class="h-3 w-3 shrink-0 rounded-full" :style="'background-color:' + (badges[scope] ? badges[scope].color : '#f59e0b')"></span>
                                <span class="text-xs font-medium text-gray-700" x-text="badges[scope] ? badges[scope].label : scope"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <p x-show="manualModal.error" x-cloak class="text-sm text-red-600" x-text="manualModal.error"></p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="closeManualModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</button>
                    <button type="submit" :disabled="manualModal.saving" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
function holidaysPage(cities, national, manual, badges, manualScopes, states) {
    const cityBase = <?= json_encode(site_url('settings/holidays/city')) ?>;
    const cityUpdateBase = <?= json_encode(site_url('settings/holidays/city/update/')) ?>;
    const cityDeleteBase = <?= json_encode(site_url('settings/holidays/city/delete/')) ?>;
    const syncUrl = <?= json_encode(site_url('settings/holidays/sync-national')) ?>;
    const manualUrl = <?= json_encode(site_url('settings/holidays')) ?>;
    const manualUpdateBase = <?= json_encode(site_url('settings/holidays/update/')) ?>;
    const manualDeleteBase = <?= json_encode(site_url('settings/holidays/delete/')) ?>;

    return {
        cities,
        national,
        manual,
        badges,
        manualScopes,
        states,
        syncing: false,
        pageError: null,
        successMessage: null,
        csrf: <?= json_encode(csrf_hash()) ?>,

        cityModal: { open: false, id: null, city_name: '', state: '', city_code: '', saving: false, error: null },
        manualModal: { open: false, id: null, name: '', date: '', scope: 'municipal', saving: false, error: null },

        formatDate(value) {
            if (!value) return '';
            return new Date(value + 'T00:00:00').toLocaleDateString('pt-BR');
        },

        closeAll() {
            this.cityModal.open = false;
            this.manualModal.open = false;
        },

        flash(message, type = 'success') {
            if (type === 'error') {
                this.pageError = message;
                this.successMessage = null;
            } else {
                this.successMessage = message;
                this.pageError = null;
            }
        },

        updateCsrf(data) {
            if (data && data.csrf_token) this.csrf = data.csrf_token;
        },

        openCityModal(city) {
            this.cityModal = city
                ? { open: true, id: city.id, city_name: city.city_name, state: city.state, city_code: city.city_code, saving: false, error: null }
                : { open: true, id: null, city_name: '', state: '', city_code: '', saving: false, error: null };
        },

        closeCityModal() {
            this.cityModal.open = false;
        },

        async submitCity() {
            this.cityModal.error = null;
            this.cityModal.saving = true;

            const url = this.cityModal.id ? (cityUpdateBase + this.cityModal.id) : cityBase;
            const params = new URLSearchParams();
            params.set('city_name', this.cityModal.city_name);
            params.set('state', this.cityModal.state);
            params.set('city_code', this.cityModal.city_code);

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString(),
                });
                const data = await res.json();
                this.updateCsrf(data);

                if (!data.success) {
                    this.cityModal.error = data.message || 'Não foi possível salvar a cidade.';
                    this.cityModal.saving = false;
                    return;
                }

                if (data.cities) this.cities = data.cities;
                this.closeCityModal();
                this.flash(data.message);
            } catch (e) {
                this.cityModal.error = 'Falha na comunicação com o servidor.';
            }

            this.cityModal.saving = false;
        },

        async removeCity(city) {
            if (!window.confirm(`Excluir a cidade "${city.city_name}"?`)) return;

            try {
                const res = await fetch(cityDeleteBase + city.id, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(),
                });
                const data = await res.json();
                this.updateCsrf(data);

                if (!data.success) {
                    this.flash(data.message || 'Não foi possível excluir a cidade.', 'error');
                    return;
                }

                if (data.cities) this.cities = data.cities;
                this.flash(data.message);
            } catch (e) {
                this.flash('Falha na comunicação com o servidor.', 'error');
            }
        },

        async syncNational() {
            this.syncing = true;
            this.pageError = null;

            const params = new URLSearchParams();
            params.set('year', String(new Date().getFullYear()));

            try {
                const res = await fetch(syncUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString(),
                });
                const data = await res.json();
                this.updateCsrf(data);

                if (!data.success) {
                    this.flash(data.message || 'Não foi possível sincronizar os feriados.', 'error');
                    this.syncing = false;
                    return;
                }

                if (data.national) this.national = data.national;
                this.flash(data.message);
            } catch (e) {
                this.flash('Falha na comunicação com o servidor.', 'error');
            }

            this.syncing = false;
        },


        openManualModal(holiday) {
            this.manualModal = holiday
                ? { open: true, id: holiday.id, name: holiday.name, date: holiday.date, scope: holiday.scope || 'municipal', saving: false, error: null }
                : { open: true, id: null, name: '', date: '', scope: 'municipal', saving: false, error: null };
        },

        closeManualModal() {
            this.manualModal.open = false;
        },

        async submitManual() {
            this.manualModal.error = null;
            this.manualModal.saving = true;

            const url = this.manualModal.id ? (manualUpdateBase + this.manualModal.id) : manualUrl;
            const params = new URLSearchParams();
            params.set('name', this.manualModal.name);
            params.set('date', this.manualModal.date);
            params.set('scope', this.manualModal.scope);

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString(),
                });
                const data = await res.json();
                this.updateCsrf(data);

                if (!data.success) {
                    this.manualModal.error = data.message || 'Não foi possível salvar o feriado.';
                    this.manualModal.saving = false;
                    return;
                }

                if (data.manual) this.manual = data.manual;
                this.closeManualModal();
                this.flash(data.message);
            } catch (e) {
                this.manualModal.error = 'Falha na comunicação com o servidor.';
            }

            this.manualModal.saving = false;
        },

        async removeManual(holiday) {
            if (!window.confirm(`Excluir o feriado "${holiday.name}"?`)) return;

            try {
                const res = await fetch(manualDeleteBase + holiday.id, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(),
                });
                const data = await res.json();
                this.updateCsrf(data);

                if (!data.success) {
                    this.flash(data.message || 'Não foi possível excluir o feriado.', 'error');
                    return;
                }

                if (data.manual) this.manual = data.manual;
                this.flash(data.message);
            } catch (e) {
                this.flash('Falha na comunicação com o servidor.', 'error');
            }
        },
    };
}
</script>

<?= $this->endSection() ?>

