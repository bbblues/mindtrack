<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<style>[x-cloak]{display:none!important}</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/pt-br.global.min.js"></script>

<div x-data="agendaApp()" x-cloak class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Agenda</h2>
            <p class="mt-1 text-sm text-gray-500">Visualize por mês, semana ou dia e arraste os cards para reagendar.</p>
        </div>
        <button type="button" @click="openModal()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            + Agendamento
        </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="p-4">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Modal: Novo Agendamento -->
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="open = false">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl" @click.outside="open = false">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-800">Novo Agendamento</h3>
                <button type="button" @click="open = false" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit()" class="space-y-4 p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="patient_id" class="mb-1 block text-sm font-medium text-gray-700">Paciente *</label>
                        <select id="patient_id" x-model="form.patient_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            <option value="">Selecione...</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?= (int) $patient['id'] ?>"><?= esc($patient['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="professional_id" class="mb-1 block text-sm font-medium text-gray-700">Profissional *</label>
                        <select id="professional_id" x-model="form.professional_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            <option value="">Selecione...</option>
                            <?php foreach ($professionals as $professional): ?>
                                <option value="<?= (int) $professional['id'] ?>"><?= esc($professional['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="unit_id" class="mb-1 block text-sm font-medium text-gray-700">Unidade *</label>
                        <select id="unit_id" x-model="form.unit_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            <option value="">Selecione...</option>
                            <?php foreach ($units as $unit): ?>
                                <option value="<?= (int) $unit['id'] ?>"><?= esc($unit['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="room_id" class="mb-1 block text-sm font-medium text-gray-700">Sala (opcional)</label>
                        <select id="room_id" x-model="form.room_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            <option value="">Selecione...</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= (int) $room['id'] ?>"><?= esc($room['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="date" class="mb-1 block text-sm font-medium text-gray-700">Data *</label>
                        <input type="date" id="date" x-model="form.date" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="start_time" class="mb-1 block text-sm font-medium text-gray-700">Início *</label>
                            <input type="time" id="start_time" x-model="form.start_time" @change="onStartChange()" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        </div>
                        <div>
                            <label for="end_time" class="mb-1 block text-sm font-medium text-gray-700">Fim</label>
                            <input type="time" id="end_time" x-model="form.end_time" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        </div>
                    </div>

                    <div>
                        <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" x-model="form.status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            <?php foreach ($statusOptions as $statusValue => $statusLabel): ?>
                                <option value="<?= esc($statusValue) ?>"><?= esc($statusLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex items-end pb-2">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" x-model="form.is_recurring" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            Recorrente (semanal)
                        </label>
                    </div>

                    <div x-show="form.is_recurring" x-cloak>
                        <label for="recurrence_end_date" class="mb-1 block text-sm font-medium text-gray-700">Até (data final)</label>
                        <input type="date" id="recurrence_end_date" x-model="form.recurrence_end_date" :required="form.is_recurring" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes" class="mb-1 block text-sm font-medium text-gray-700">Observações</label>
                        <textarea id="notes" x-model="form.notes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4">
                    <button type="button" @click="open = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</button>
                    <button type="submit" :disabled="submitting" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Conflito de horário -->
    <div x-show="conflict.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.outside="conflict.open = false">
            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100 text-yellow-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Conflito de horário</h3>
            <p class="mt-1 text-sm text-gray-600">O profissional já possui agendamento(s) neste horário:</p>
            <ul class="mt-3 space-y-2">
                <template x-for="conflict in conflict.conflicts" :key="conflict.id">
                    <li class="rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-sm text-yellow-800">
                        <span x-text="conflictLabel(conflict)"></span>
                    </li>
                </template>
            </ul>
            <p class="mt-3 text-sm font-medium text-gray-800">Deseja continuar mesmo assim?</p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" @click="conflict.open = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</button>
                <button type="button" @click="confirmOverride()" class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-yellow-600">Continuar mesmo assim</button>
            </div>
        </div>
    </div>
</div>

<script>
function agendaApp() {
    return {
        open: false,
        submitting: false,
        conflict: { open: false, conflicts: [] },
        pendingPayload: null,
        token: <?= json_encode(csrf_hash()) ?>,
        defaultDuration: <?= (int) $defaultDuration ?>,
        form: {
            patient_id: '',
            professional_id: '',
            unit_id: '',
            room_id: '',
            date: <?= json_encode($date) ?>,
            start_time: '09:00',
            end_time: '',
            status: 'agendado',
            is_recurring: false,
            recurrence_end_date: '',
            notes: '',
        },
        openModal() {
            this.form.end_time = this.addMinutes(this.form.start_time, this.defaultDuration);
            this.open = true;
        },
        onStartChange() {
            if (! this.form.end_time) {
                this.form.end_time = this.addMinutes(this.form.start_time, this.defaultDuration);
            }
        },
        addMinutes(time, minutes) {
            if (! time) {
                return '';
            }

            const parts = time.split(':');
            const total = (parseInt(parts[0], 10) * 60) + parseInt(parts[1], 10) + minutes;
            const hour = Math.floor(total / 60) % 24;
            const minute = total % 60;

            return String(hour).padStart(2, '0') + ':' + String(minute).padStart(2, '0');
        },
        buildPayload(forceOverride) {
            const combine = (time) => time ? (this.form.date + ' ' + time + ':00') : null;

            return {
                patient_id: this.form.patient_id ? Number(this.form.patient_id) : null,
                professional_id: this.form.professional_id ? Number(this.form.professional_id) : null,
                unit_id: this.form.unit_id ? Number(this.form.unit_id) : null,
                room_id: this.form.room_id ? Number(this.form.room_id) : null,
                start_time: combine(this.form.start_time),
                end_time: combine(this.form.end_time),
                status: this.form.status,
                is_recurring: !! this.form.is_recurring,
                recurrence_end_date: this.form.recurrence_end_date || null,
                notes: this.form.notes || '',
                force_override: !! forceOverride,
            };
        },
        submit() {
            this.post(this.buildPayload(false));
        },
        confirmOverride() {
            this.conflict.open = false;

            if (this.pendingPayload) {
                this.pendingPayload.force_override = true;
                this.post(this.pendingPayload);
            }
        },
        conflictLabel(conflict) {
            const when = (conflict.start_time || '').replace('T', ' ').slice(0, 16);

            return (conflict.patient_name || 'Paciente') + ' às ' + when;
        },
        async post(payload) {
            this.submitting = true;

            try {
                const response = await fetch(<?= json_encode(site_url('agenda/store')) ?>, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (data.csrf_hash) {
                    this.token = data.csrf_hash;
                }

                if (response.status === 409 && data.conflict) {
                    this.pendingPayload = payload;
                    this.conflict.conflicts = data.conflicts || [];
                    this.conflict.open = true;
                    this.open = false;
                    return;
                }

                if (! data.success) {
                    const details = data.errors ? Object.values(data.errors).join('\n') : '';
                    alert(data.message || 'Erro ao salvar o agendamento.' + (details ? '\n' + details : ''));

                    return;
                }

                window.location.reload();
            } catch (error) {
                alert('Erro ao salvar o agendamento.');
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof FullCalendar === 'undefined') {
        return;
    }

    let csrfToken = <?= json_encode(csrf_hash()) ?>;
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        timeZone: 'UTC',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: true,
        eventDurationEditable: false,
        events: {
            url: <?= json_encode(site_url('agenda/events')) ?>,
            method: 'GET',
            failure: () => alert('Falha ao carregar os agendamentos.')
        },
        eventDrop: (info) => handleEventDrop(info),
    });

    calendar.render();

    async function handleEventDrop(info) {
        const payload = {
            id: info.event.id,
            start: info.event.startStr,
            end: info.event.endStr || info.event.startStr,
        };

        try {
            const result = await postUpdate(payload);

            if (result.data.csrf_hash) {
                csrfToken = result.data.csrf_hash;
            }

            if (result.status === 409) {
                const proceed = confirm('Conflito de horário para o profissional. Deseja continuar mesmo assim?');

                if (! proceed) {
                    info.revert();
                    return;
                }

                const forced = await postUpdate({
                    id: payload.id,
                    start: payload.start,
                    end: payload.end,
                    force_override: true,
                });

                if (forced.data.csrf_hash) {
                    csrfToken = forced.data.csrf_hash;
                }

                if (forced.status !== 200) {
                    info.revert();
                    alert(forced.data.message || 'Não foi possível mover o agendamento.');
                }

                return;
            }

            if (result.status !== 200) {
                info.revert();
                alert(result.data.message || 'Não foi possível mover o agendamento.');
            }
        } catch (error) {
            info.revert();
            alert('Erro ao mover o agendamento.');
        }
    }

    async function postUpdate(payload) {
        const response = await fetch(<?= json_encode(site_url('agenda/update-schedule')) ?>, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        let data = {};
        try {
            data = await response.json();
        } catch (error) {
            // Resposta sem corpo JSON.
        }

        return { status: response.status, data };
    }
});
</script>

<?= $this->endSection() ?>

