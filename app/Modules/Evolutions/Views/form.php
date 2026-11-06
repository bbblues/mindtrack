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

$evolutionStatus     = $evolution['status'] ?? 'draft';
$isFinalized         = $evolutionStatus === 'finalized';
$initialEvolutionId  = isset($evolution['id']) ? (int) $evolution['id'] : 0;
$initialText         = $evolution['evolution_text'] ?? '';
$initialConfidential = (int) ($evolution['is_confidential'] ?? 0);
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

    <div x-show="readOnly" x-cloak class="mb-4 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m0-6v.01M6.75 11V7.5a5.25 5.25 0 0110.5 0V11m-10.5 0H5a1 1 0 00-1 1v7a1 1 0 001 1h14a1 1 0 001-1v-7a1 1 0 00-1-1h-1.75" />
        </svg>
        <div>
            <p class="font-semibold">Evolução finalizada</p>
            <p class="mt-0.5">Finalizada em <span x-text="finalizedAtLabel()"></span> por <span x-text="finalizedByName || '—'"></span>. Esta evolução não pode mais ser editada.</p>
        </div>
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
                <select id="template_id" x-model="form.template_id" @change="applyTemplate()" :disabled="readOnly" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400">
                    <option value="">Selecione um modelo...</option>
                    <template x-for="template in templates" :key="template.id">
                        <option :value="template.id" x-text="template.name"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="evolution_text" class="mb-1 block text-sm font-medium text-gray-700">Evolução *</label>
                <textarea id="evolution_text" x-model="form.evolution_text" rows="12" required placeholder="Descreva como foi a sessão, as atividades realizadas e o progresso do paciente..." :readonly="readOnly" :disabled="readOnly" @input="markDirty()" @blur="autoSave()" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400"></textarea>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" x-model="isConfidential" @change="markDirty()" :disabled="readOnly" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:cursor-not-allowed">
                Tornar nota confidencial
            </label>
            <p class="-mt-2 text-xs text-gray-400">Notas confidenciais só são visíveis pelo autor.</p>

            <div class="flex items-center justify-between text-xs">
                <span x-show="!readOnly && dirty" class="font-medium text-amber-600">Alterações não salvas</span>
                <span x-show="!readOnly && !dirty && lastSavedAt" class="text-gray-400" x-text="lastSavedLabel()"></span>
                <span x-show="readOnly" class="font-medium text-green-600">Evolução finalizada (imutável)</span>
                <span></span>
            </div>

            <div x-show="!readOnly" class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4">
                <a href="<?= site_url('agenda') ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</a>
                <button type="button" @click="saveDraft()" :disabled="submitting" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50">Salvar Rascunho</button>
                <button type="button" @click="requestFinalize()" :disabled="submitting" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50">Finalizar Evolução</button>
            </div>
        </form>
    </div>

    <div x-show="showConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="showConfirm = false">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.outside="showConfirm = false">
            <h3 class="text-lg font-semibold text-gray-800">Finalizar evolução</h3>
            <p class="mt-2 text-sm text-gray-600">Atenção: Após finalizar, não será possível editar esta evolução. Deseja continuar?</p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="showConfirm = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</button>
                <button type="button" @click="confirmFinalize()" :disabled="submitting" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
function evolutionForm() {
    return {
        appointmentId: <?= (int) ($appointment['id'] ?? 0) ?>,
        evolutionId: <?= $initialEvolutionId ?>,
        status: <?= json_encode($evolutionStatus) ?>,
        readOnly: <?= $isFinalized ? 'true' : 'false' ?>,
        finalizedAt: <?= json_encode($evolution['finalized_at'] ?? null) ?>,
        finalizedByName: <?= json_encode($finalizedByName ?? null) ?>,
        templates: <?= json_encode($noteTemplates, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        token: <?= json_encode(csrf_hash()) ?>,
        storeUrl: <?= json_encode(site_url('evolutions/store')) ?>,
        updateUrl: <?= json_encode(site_url('evolutions/update')) ?>,
        finalizeUrl: <?= json_encode(site_url('evolutions/finalize')) ?>,
        redirectUrl: <?= json_encode(site_url('evolutions/' . (int) ($appointment['patient_id'] ?? 0))) ?>,
        submitting: false,
        dirty: false,
        lastSavedAt: null,
        showConfirm: false,
        autoSaveInterval: null,
        debounceTimer: null,
        form: {
            template_id: '',
            evolution_text: <?= json_encode($initialText, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        },
        isConfidential: <?= $initialConfidential ? 'true' : 'false' ?>,

        init() {
            if (this.readOnly) {
                return;
            }

            this.autoSaveInterval = setInterval(() => this.autoSave(), 30000);

            window.addEventListener('beforeunload', (event) => {
                if (!this.readOnly && this.dirty) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            });
        },

        markDirty() {
            if (this.readOnly) {
                return;
            }

            this.dirty = true;
            this.scheduleAutoSave();
        },

        scheduleAutoSave() {
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }

            this.debounceTimer = setTimeout(() => this.autoSave(), 2000);
        },

        applyTemplate() {
            if (!this.form.template_id) {
                return;
            }

            const template = this.templates.find((t) => String(t.id) === String(this.form.template_id));
            if (template) {
                this.form.evolution_text = template.content || '';
                this.markDirty();
            }
        },

        async autoSave() {
            if (this.readOnly || !this.dirty) {
                return;
            }

            if (!this.form.evolution_text.trim()) {
                return;
            }

            await this.persist(false);
        },

        async saveDraft() {
            if (!this.form.evolution_text.trim()) {
                alert('O texto da evolução é obrigatório.');
                return;
            }

            await this.persist(false);
        },

        async submit() {
            await this.saveDraft();
        },

        async requestFinalize() {
            if (!this.form.evolution_text.trim()) {
                alert('O texto da evolução é obrigatório.');
                return;
            }

            this.showConfirm = true;
        },

        async confirmFinalize() {
            this.showConfirm = false;
            await this.persist(true);
        },
        async persist(finalize) {
            this.submitting = true;

            const body = {
                appointment_id: this.appointmentId,
                evolution_text: this.form.evolution_text,
                is_confidential: this.isConfidential,
            };

            let url;
            if (finalize) {
                url = this.evolutionId ? (this.finalizeUrl + '/' + this.evolutionId) : this.storeUrl;
                body.finalize = true;
            } else {
                url = this.evolutionId ? (this.updateUrl + '/' + this.evolutionId) : this.storeUrl;
                body.finalize = false;
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                });

                const data = await response.json();

                if (data.csrf_hash) {
                    this.token = data.csrf_hash;
                }

                if (!data.success) {
                    if (response.status === 403) {
                        this.readOnly = true;
                        this.status = 'finalized';
                        window.location.reload();
                        return false;
                    }

                    const details = data.errors ? Object.values(data.errors).join('\n') : '';
                    alert((data.message || 'Erro ao salvar a evolução.') + (details ? '\n' + details : ''));
                    return false;
                }

                this.evolutionId = data.evolution_id;
                this.status = data.status;
                this.dirty = false;
                this.lastSavedAt = new Date();

                if (data.status === 'finalized') {
                    this.readOnly = true;
                    this.finalizedAt = data.finalized_at || this.finalizedAt;
                    this.finalizedByName = data.finalized_by_name || this.finalizedByName;
                }

                return true;
            } catch (error) {
                alert('Erro ao salvar a evolução.');
                return false;
            } finally {
                this.submitting = false;
            }
        },

        lastSavedLabel() {
            if (!this.lastSavedAt) {
                return 'Rascunho não salvo';
            }

            const pad = (n) => String(n).padStart(2, '0');
            const t = this.lastSavedAt;
            return 'Salvo às ' + pad(t.getHours()) + ':' + pad(t.getMinutes()) + ':' + pad(t.getSeconds());
        },

        finalizedAtLabel() {
            if (!this.finalizedAt) {
                return '—';
            }

            const value = String(this.finalizedAt).replace(' ', 'T');
            const d = new Date(value);

            if (isNaN(d.getTime())) {
                return String(this.finalizedAt);
            }

            const pad = (n) => String(n).padStart(2, '0');
            return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        },
    };
}
</script>

<?= $this->endSection() ?>
