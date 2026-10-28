<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$patientName = isset($patient['name']) ? (string) $patient['name'] : 'Paciente';
$professionalName = isset($professional['name']) ? (string) $professional['name'] : 'Profissional';

$formatDateTime = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp === false ? $value : date('d/m/Y H:i', $timestamp);
};
?>

<style>[x-cloak]{display:none!important}</style>

<div class="mx-auto max-w-3xl" x-data="evolutionForm()" x-cloak>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800"><?= esc($title) ?></h2>
            <p class="mt-1 text-sm text-gray-500">Registre a evolução da sessão do paciente.</p>
        </div>
        <a href="<?= site_url('agenda') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Voltar para a Agenda</a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wide text-gray-400">Paciente</span>
                    <span class="mt-1 block font-medium text-gray-800"><?= esc($patientName) ?></span>
                </div>
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wide text-gray-400">Profissional</span>
                    <span class="mt-1 block font-medium text-gray-800"><?= esc($professionalName) ?></span>
                </div>
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wide text-gray-400">Data/Hora</span>
                    <span class="mt-1 block font-medium text-gray-800"><?= esc($formatDateTime($appointment['start_time'] ?? null)) ?> – <?= esc($formatDateTime($appointment['end_time'] ?? null)) ?></span>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit()" class="space-y-4 p-6">
            <div>
                <label for="template_id" class="mb-1 block text-sm font-medium text-gray-700">Modelo de Nota</label>
                <select id="template_id" x-model="form.template_id" @change="applyTemplate()" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    <option value="">Selecione um modelo...</option>
                    <template x-for="template in templates" :key="template.id">
                        <option :value="template.id" x-text="template.name"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="evolution_text" class="mb-1 block text-sm font-medium text-gray-700">Evolução *</label>
                <textarea id="evolution_text" x-model="form.evolution_text" rows="12" required placeholder="Descreva como foi a sessão, as atividades realizadas e o progresso do paciente..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4">
                <a href="<?= site_url('agenda') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</a>
                <button type="submit" :disabled="submitting" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50">Salvar Evolução</button>
            </div>
        </form>
    </div>
</div>

<script>
function evolutionForm() {
    return {
        appointmentId: <?= (int) ($appointment['id'] ?? 0) ?>,
        templates: <?= json_encode($noteTemplates, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        token: <?= json_encode(csrf_hash()) ?>,
        storeUrl: <?= json_encode(site_url('evolutions/store')) ?>,
        redirectUrl: <?= json_encode(site_url('evolutions/' . (int) ($appointment['patient_id'] ?? 0))) ?>,
        submitting: false,
        form: {
            template_id: '',
            evolution_text: '',
        },
        applyTemplate() {
            if (! this.form.template_id) {
                return;
            }

            const template = this.templates.find((t) => String(t.id) === String(this.form.template_id));
            if (template) {
                this.form.evolution_text = template.content || '';
            }
        },
        async submit() {
            if (! this.form.evolution_text.trim()) {
                alert('O texto da evolução é obrigatório.');
                return;
            }

            this.submitting = true;

            try {
                const response = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        appointment_id: this.appointmentId,
                        evolution_text: this.form.evolution_text,
                    }),
                });

                const data = await response.json();

                if (data.csrf_hash) {
                    this.token = data.csrf_hash;
                }

                if (! data.success) {
                    const details = data.errors ? Object.values(data.errors).join('\n') : '';
                    alert((data.message || 'Erro ao salvar a evolução.') + (details ? '\n' + details : ''));
                    return;
                }

                window.location.href = this.redirectUrl;
            } catch (error) {
                alert('Erro ao salvar a evolução.');
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>

<?= $this->endSection() ?>
