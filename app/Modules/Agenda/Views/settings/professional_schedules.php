<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<style>[x-cloak]{display:none !important;}</style>

<?php
$professionalsJson = [];
foreach ($professionals as $professional) {
    $professionalsJson[] = ['id' => (int) $professional['id'], 'name' => (string) $professional['name']];
}
?>

<div
    x-data='professionalSchedulesPage(<?= esc(json_encode($professionalsJson, JSON_UNESCAPED_UNICODE), 'attr') ?>, <?= esc(json_encode($schedulesByProfessional, JSON_UNESCAPED_UNICODE), 'attr') ?>)'
    class="space-y-6"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Disponibilidade dos Profissionais</h2>
            <p class="mt-1 text-sm text-gray-500">Configure os dias da semana e os horários em que cada profissional atende pacientes.</p>
        </div>
    </div>

    <div x-show="pageError" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="pageError"></div>
    <div x-show="successMessage" x-cloak class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700" x-text="successMessage"></div>

    <div x-show="professionals.length === 0" x-cloak class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-16 text-center">
        <h3 class="text-lg font-semibold text-gray-800">Nenhum profissional cadastrado</h3>
        <p class="mt-1 text-sm text-gray-500">Cadastre um usuário com o cargo "Profissional" para configurar a agenda dele.</p>
    </div>

    <div x-show="professionals.length > 0" x-cloak class="space-y-4">
        <div class="max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <label for="professional" class="mb-1 block text-sm font-medium text-gray-700">Profissional</label>
            <select
                id="professional"
                x-model="selectedId"
                @change="selectProfessional()"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
            >
                <template x-for="p in professionals" :key="p.id">
                    <option :value="String(p.id)" x-text="p.name"></option>
                </template>
            </select>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="divide-y divide-gray-100">
                <template x-for="row in rows" :key="row.day">
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-start">
                        <div class="w-40 shrink-0 pt-2">
                            <span class="text-sm font-semibold text-gray-800" x-text="row.label"></span>
                        </div>
                        <div class="flex-1 space-y-2">
                            <template x-for="(slot, index) in row.slots" :key="index">
                                <div class="flex items-center gap-2">
                                    <input type="time" x-model="slot.start_time" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                                    <span class="text-sm text-gray-400">até</span>
                                    <input type="time" x-model="slot.end_time" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                                    <button type="button" @click="removeSlot(row, index)" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-600 transition hover:bg-red-50">Remover</button>
                                </div>
                            </template>
                            <button type="button" @click="addSlot(row)" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">+ Adicionar horário</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button type="button" @click="save()" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60">
                <span x-show="saving" x-cloak>Salvando…</span>
                <span x-show="!saving">Salvar disponibilidade</span>
            </button>
        </div>
    </div>
</div>

<script>
function professionalSchedulesPage(professionals, initialSchedules) {
    const DAYS = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];

    function blankRows() {
        return DAYS.map((label, day) => ({ day, label, slots: [{ start_time: '', end_time: '' }] }));
    }

    function fromMap(map) {
        const rows = blankRows();

        if (map) {
            Object.keys(map).forEach((day) => {
                const index = Number(day);
                const list = map[day] || [];
                rows[index].slots = list.length
                    ? list.map((slot) => ({ start_time: slot.start_time, end_time: slot.end_time }))
                    : [{ start_time: '', end_time: '' }];
            });
        }

        return rows;
    }

    return {
        professionals,
        initialSchedules,
        selectedId: professionals.length ? String(professionals[0].id) : '',
        rows: [],
        saving: false,
        pageError: null,
        successMessage: null,
        csrf: <?= json_encode(csrf_hash()) ?>,

        init() {
            this.rows = fromMap(this.initialSchedules[this.selectedId]);
        },

        selectProfessional() {
            this.rows = fromMap(this.initialSchedules[this.selectedId]);
            this.pageError = null;
            this.successMessage = null;
        },

        addSlot(row) {
            row.slots.push({ start_time: '', end_time: '' });
        },

        removeSlot(row, index) {
            if (row.slots.length <= 1) {
                row.slots[0] = { start_time: '', end_time: '' };
            } else {
                row.slots.splice(index, 1);
            }
        },

        async save() {
            this.pageError = null;
            this.successMessage = null;

            const days = {};
            this.rows.forEach((row) => {
                const slots = row.slots.filter((slot) => slot.start_time || slot.end_time);
                if (slots.length) {
                    days[String(row.day)] = slots;
                }
            });

            for (const day of Object.keys(days)) {
                for (const slot of days[day]) {
                    if (!slot.start_time || !slot.end_time) {
                        this.pageError = 'Preencha início e fim de todos os horários (ou remova o horário).';
                        return;
                    }
                    if (slot.end_time <= slot.start_time) {
                        this.pageError = 'O horário de fim deve ser posterior ao horário de início.';
                        return;
                    }
                }
            }

            this.saving = true;

            try {
                const res = await fetch(<?= json_encode(site_url('settings/professional-schedules')) ?>, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ professional_id: Number(this.selectedId), days }),
                });

                const data = await res.json();

                if (data.csrf_token) {
                    this.csrf = data.csrf_token;
                }

                if (!data.success) {
                    this.pageError = data.message || 'Não foi possível salvar a disponibilidade.';
                    this.saving = false;
                    return;
                }

                if (data.schedule) {
                    this.initialSchedules[this.selectedId] = data.schedule;
                    this.rows = fromMap(data.schedule);
                }

                this.successMessage = data.message || 'Disponibilidade salva com sucesso.';
            } catch (e) {
                this.pageError = 'Falha na comunicação com o servidor.';
            }

            this.saving = false;
        },
    };
}
</script>

<?= $this->endSection() ?>

