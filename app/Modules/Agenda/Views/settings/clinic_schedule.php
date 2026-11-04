<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<style>[x-cloak]{display:none !important;}</style>

<?php
$scheduleJson = [];
foreach ($schedule as $day => $interval) {
    $scheduleJson[(string) $day] = [
        'start_time' => (string) ($interval['start_time'] ?? ''),
        'end_time'   => (string) ($interval['end_time'] ?? ''),
    ];
}
?>

<div x-data="clinicSchedulePage(<?= esc(json_encode($scheduleJson, JSON_UNESCAPED_UNICODE), 'attr') ?>)" class="space-y-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Horário de Funcionamento</h2>
        <p class="mt-1 text-sm text-gray-500">Defina os dias e horários em que a clínica está aberta. A agenda exibirá apenas esse período.</p>
    </div>

    <div x-show="pageError" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="pageError"></div>
    <div x-show="successMessage" x-cloak class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700" x-text="successMessage"></div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="divide-y divide-gray-100">
            <template x-for="row in rows" :key="row.day">
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                    <div class="w-40 shrink-0">
                        <span class="text-sm font-semibold text-gray-800" x-text="row.label"></span>
                    </div>
                    <div class="flex flex-1 items-center gap-2">
                        <input type="time" x-model="row.start_time" :disabled="row.closed" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 disabled:bg-gray-50 disabled:text-gray-400">
                        <span class="text-sm text-gray-400">até</span>
                        <input type="time" x-model="row.end_time" :disabled="row.closed" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 disabled:bg-gray-50 disabled:text-gray-400">
                    </div>
                    <label class="flex shrink-0 items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" x-model="row.closed" @change="onClosedChange(row)" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>Fechado</span>
                    </label>
                </div>
            </template>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <button type="button" @click="save()" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60">
            <span x-show="saving" x-cloak>Salvando…</span>
            <span x-show="!saving">Salvar horário de funcionamento</span>
        </button>
    </div>
</div>

<script>
function clinicSchedulePage(initialSchedule) {
    const DAYS = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];

    function blankRows() {
        return DAYS.map((label, day) => ({ day, label, closed: true, start_time: '', end_time: '' }));
    }

    function fromMap(map) {
        const rows = blankRows();

        if (map) {
            Object.keys(map).forEach((day) => {
                const index = Number(day);
                const interval = map[day] || {};
                rows[index].closed = false;
                rows[index].start_time = interval.start_time || '';
                rows[index].end_time = interval.end_time || '';
            });
        }

        return rows;
    }

    return {
        rows: [],
        saving: false,
        pageError: null,
        successMessage: null,
        csrf: <?= json_encode(csrf_hash()) ?>,

        init() {
            this.rows = fromMap(initialSchedule);
        },

        onClosedChange(row) {
            if (row.closed) {
                row.start_time = '';
                row.end_time = '';
            }
        },

        async save() {
            this.pageError = null;
            this.successMessage = null;

            const days = {};

            for (const row of this.rows) {
                if (row.closed) {
                    continue;
                }

                if (!row.start_time || !row.end_time) {
                    this.pageError = 'Preencha início e fim de todos os dias abertos (ou marque como "Fechado").';
                    return;
                }

                if (row.end_time <= row.start_time) {
                    this.pageError = 'O horário de fim deve ser posterior ao horário de início (' + row.label + ').';
                    return;
                }

                days[String(row.day)] = { start_time: row.start_time, end_time: row.end_time };
            }

            this.saving = true;

            try {
                const res = await fetch(<?= json_encode(site_url('settings/clinic-schedule')) ?>, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ days }),
                });

                const data = await res.json();

                if (data.csrf_token) {
                    this.csrf = data.csrf_token;
                }

                if (!data.success) {
                    this.pageError = data.message || 'Não foi possível salvar o horário de funcionamento.';
                    this.saving = false;
                    return;
                }

                if (data.schedule) {
                    this.rows = fromMap(data.schedule);
                }

                this.successMessage = data.message || 'Horário de funcionamento salvo com sucesso.';
            } catch (e) {
                this.pageError = 'Falha na comunicação com o servidor.';
            }

            this.saving = false;
        },
    };
}
</script>

<?= $this->endSection() ?>

