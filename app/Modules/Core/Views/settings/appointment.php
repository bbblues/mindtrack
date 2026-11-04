<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<style>[x-cloak]{display:none !important;}</style>

<div x-data="appointmentSettings(<?= (int) $defaultDuration ?>)" class="space-y-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Configurações da Agenda</h2>
        <p class="mt-1 text-sm text-gray-500">Defina a duração padrão aplicada ao criar novos agendamentos.</p>
    </div>

    <div class="max-w-xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div x-show="pageError" x-cloak class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="pageError"></div>
        <div x-show="successMessage" x-cloak class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700" x-text="successMessage"></div>

        <form @submit.prevent="submit()">
            <label for="duration" class="mb-1 block text-sm font-medium text-gray-700">Duração padrão da sessão (minutos) *</label>
            <input
                id="duration"
                type="number"
                min="5"
                max="480"
                step="1"
                required
                x-model.number="duration"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
            >
            <p class="mt-2 text-xs text-gray-500">Valor permitido entre 5 e 480 minutos (8 horas).</p>

            <div class="mt-4 flex items-center gap-3">
                <button type="submit" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="saving" x-cloak>Salvando…</span>
                    <span x-show="!saving">Salvar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function appointmentSettings(initialDuration) {
    return {
        duration: initialDuration,
        saving: false,
        pageError: null,
        successMessage: null,
        csrf: <?= json_encode(csrf_hash()) ?>,

        async submit() {
            this.pageError = null;
            this.successMessage = null;
            this.saving = true;

            const params = new URLSearchParams();
            params.set('duration', String(this.duration));

            try {
                const res = await fetch(<?= json_encode(site_url('settings/appointment')) ?>, {
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
                    this.pageError = data.errors && Object.keys(data.errors).length
                        ? Object.values(data.errors).join(' ')
                        : (data.message || 'Não foi possível salvar a configuração.');
                    this.saving = false;
                    return;
                }

                if (data.duration) {
                    this.duration = data.duration;
                }

                this.successMessage = data.message || 'Configuração salva com sucesso.';
            } catch (e) {
                this.pageError = 'Falha na comunicação com o servidor.';
            }

            this.saving = false;
        }
    };
}
</script>

<?= $this->endSection() ?>
