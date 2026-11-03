<?php
/**
 * Busca Rápida de Pacientes (Command Palette global).
 *
 * Incluído na Topbar do layout. Depende de Alpine.js e Tailwind CSS,
 * já carregados em app/Views/layouts/app.php.
 */
?>
<div
    x-data="quickSearch()"
    x-cloak
    @keydown.ctrl.k.window.prevent="openPalette()"
    @keydown.meta.k.window.prevent="openPalette()"
>
    <!-- Trigger -->
    <button
        type="button"
        @click="openPalette()"
        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-500 transition hover:border-blue-400 hover:text-blue-600"
        aria-label="Buscar paciente"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span class="hidden lg:inline">Buscar paciente…</span>
        <kbd class="hidden rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-400 lg:inline">Ctrl+K</kbd>
    </button>

    <!-- Modal -->
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[70] flex items-start justify-center bg-black/50 p-4 pt-[10vh]"
        x-transition.opacity.duration.150ms
        @keydown.escape.window="close()"
        aria-modal="true"
        role="dialog"
        aria-label="Buscar paciente"
    >
        <div
            class="w-full max-w-xl overflow-hidden rounded-xl bg-white shadow-2xl"
            @click.outside="close()"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="selectActive()"
        >
            <div class="flex items-center gap-3 border-b border-gray-200 px-4 py-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    x-ref="input"
                    type="search"
                    x-model="query"
                    @input.debounce.250ms="search()"
                    placeholder="Buscar paciente por nome ou CPF…"
                    class="flex-1 border-0 bg-transparent text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                >
                <button type="button" @click="close()" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="max-h-[60vh] overflow-y-auto p-2">
                <!-- Loading -->
                <div x-show="loading" class="flex items-center gap-2 px-4 py-6 text-sm text-gray-500">
                    <svg class="h-4 w-4 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Buscando…
                </div>

                <!-- Erro -->
                <div x-show="error" x-cloak class="px-4 py-6 text-center text-sm text-red-600" x-text="error"></div>

                <!-- Dica inicial -->
                <div x-show="!loading && !error && !searched" class="px-4 py-8 text-center text-sm text-gray-400">
                    Digite ao menos 2 caracteres para buscar.
                </div>

                <!-- Vazio -->
                <div x-show="!loading && !error && searched && results.length === 0" class="flex flex-col items-center justify-center px-6 py-10 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-700">Nenhum paciente encontrado</p>
                    <p class="mt-1 text-sm text-gray-500">Tente buscar por outro nome ou CPF.</p>
                    <a href="<?= site_url('patients/create') ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">+ Adicionar Paciente</a>
                </div>

                <!-- Resultados -->
                <template x-for="(r, i) in results" :key="r.id">
                    <div
                        class="flex items-center gap-3 rounded-lg px-3 py-2 transition"
                        :class="i === activeIndex ? 'bg-blue-50' : ''"
                        :data-active="i === activeIndex"
                        @mouseenter="activeIndex = i"
                    >
                        <span class="inline-block h-3.5 w-3.5 shrink-0 rounded-full ring-1 ring-black/10" :style="'background-color:' + r.color"></span>

                        <a :href="r.profile_url" class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="truncate text-sm font-semibold text-gray-800" x-text="r.name"></span>
                                <span x-show="r.is_active !== 1" class="inline-flex shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">Inativo</span>
                            </div>
                            <div class="truncate text-xs text-gray-500">
                                <span x-show="r.cpf" x-text="maskCpf(r.cpf)"></span>
                                <span x-show="r.cpf && r.unit_name"> · </span>
                                <span x-text="r.unit_name"></span>
                            </div>
                        </a>

                        <div class="flex shrink-0 items-center gap-1">
                            <a :href="r.profile_url" title="Ver perfil" class="rounded-lg p-1.5 text-gray-400 transition hover:bg-blue-100 hover:text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            <a :href="r.assessment_url" title="Iniciar avaliação" class="rounded-lg p-1.5 text-gray-400 transition hover:bg-blue-100 hover:text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </a>
                            <a x-show="r.evolution_url" :href="r.evolution_url" title="Registrar evolução" class="rounded-lg p-1.5 text-gray-400 transition hover:bg-blue-100 hover:text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            <span x-show="!r.evolution_url" title="Sem sessão agendada" class="cursor-not-allowed rounded-lg p-1.5 text-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </span>
                            <a :href="r.agenda_url" title="Ir para agenda" class="rounded-lg p-1.5 text-gray-400 transition hover:bg-blue-100 hover:text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </template>
            </div>

            <div class="border-t border-gray-100 px-4 py-2 text-right text-[11px] text-gray-400">
                ↑↓ navegar · Enter abrir perfil · Esc fechar
            </div>
        </div>
    </div>
</div>

<script>
function quickSearch() {
    return {
        open: false,
        query: '',
        results: [],
        loading: false,
        searched: false,
        error: '',
        activeIndex: -1,
        controller: null,
        searchUrl: <?= json_encode(site_url('patients/search')) ?>,

        openPalette() {
            this.open = true;
            this.$nextTick(() => this.$refs.input.focus());
        },

        close() {
            this.open = false;
            this.query = '';
            this.results = [];
            this.searched = false;
            this.error = '';
            this.activeIndex = -1;
        },

        async search() {
            if (this.controller) {
                this.controller.abort();
            }

            const q = (this.query || '').trim();

            if (q.length < 2) {
                this.results = [];
                this.searched = false;
                this.error = '';
                this.loading = false;
                return;
            }

            this.loading = true;
            this.error = '';
            this.activeIndex = -1;
            this.controller = new AbortController();

            try {
                const response = await fetch(this.searchUrl + '?q=' + encodeURIComponent(q), {
                    signal: this.controller.signal,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                const data = await response.json();

                if (! response.ok || ! data.success) {
                    this.error = data.message || 'Erro ao buscar pacientes.';
                    this.results = [];
                } else {
                    this.results = data.results || [];
                    this.error = '';
                }

                this.searched = true;
            } catch (e) {
                if (e.name !== 'AbortError') {
                    this.error = 'Falha ao buscar pacientes. Tente novamente.';
                    this.results = [];
                    this.searched = true;
                }
            } finally {
                this.loading = false;
            }
        },

        move(dir) {
            if (this.results.length === 0) {
                return;
            }

            this.activeIndex = (this.activeIndex + dir + this.results.length) % this.results.length;
            this.scrollActiveIntoView();
        },

        selectActive() {
            if (this.results.length === 0) {
                return;
            }

            const index = this.activeIndex >= 0 ? this.activeIndex : 0;
            window.location.href = this.results[index].profile_url;
        },

        scrollActiveIntoView() {
            this.$nextTick(() => {
                const active = this.$el.querySelector('[data-active="true"]');
                if (active) {
                    active.scrollIntoView({ block: 'nearest' });
                }
            });
        },

        maskCpf(cpf) {
            const digits = (cpf || '').replace(/\D/g, '').slice(0, 11);
            if (digits.length !== 11) {
                return cpf || '—';
            }
            return digits
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        },
    };
}
</script>

