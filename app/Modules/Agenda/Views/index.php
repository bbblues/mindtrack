<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<style>
[x-cloak]{display:none!important}
.agenda-event-name{font-weight:bold;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.agenda-event-time{font-size:11px;line-height:1.3;opacity:.75;font-weight:normal}
#weekView{overflow-x:hidden}

/* Cards de agendamento: preenchidos pela cor do paciente, sem barra/borda lateral */
.agenda-event-card.scheduler_default_event,
.agenda-event-card.calendar_default_event{border:none!important;box-shadow:none!important}
.agenda-event-card .scheduler_default_event_inner,
.agenda-event-card .calendar_default_event_inner{font-weight:bold!important;padding:4px 6px!important;border:none!important}
.agenda-event-card .scheduler_default_event_bar,
.agenda-event-card .calendar_default_event_bar,
.agenda-event-card .scheduler_default_event_bar_background,
.agenda-event-card .calendar_default_event_bar_background{display:none!important}

/* Desativa a cor cinza nativa de horários fechados (non-business hours).
   O tema padrão aplica o cinza na célula base; as classes *_cell_business
   marcam o horário comercial. Sobrescreve tudo com !important. */
.dp-non-business,
.scheduler_default_cell_business,
.calendar_default_cell_business,
.scheduler_default_cell,
.calendar_default_cell{background-color:inherit!important}

/* Força as cores da zebra (classes aplicadas via onBeforeCellRender) */
.zebra-even{background-color:#ffffff!important}
.zebra-odd{background-color:#f8f9fa!important}

</style>
<!-- This software includes DayPilot (https://www.daypilot.org). License: Apache License 2.0 -->
<script src="https://unpkg.com/@daypilot/daypilot-lite-javascript@6.2.0/daypilot-javascript.min.js"></script>

<div x-data="agendaApp()" x-cloak class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Agenda</h2>
            <p class="mt-1 text-sm text-gray-500">Visualize por dia ou semana e arraste os cards para reagendar ou trocar de profissional.</p>
        </div>
        <button type="button" @click="openModal()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            + Agendamento
        </button>
    </div>

    <div x-ref="fullscreenContainer" class="space-y-6" :class="isFullscreen ? 'overflow-auto bg-gray-100 p-4' : ''">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 shadow-sm">
                <button type="button" :class="dayButtonClass" @click="setView('day')">Dia</button>
                <button type="button" :class="weekButtonClass" @click="setView('week')">Semana</button>
            </div>

            <div x-show="view === 'day'" class="flex flex-wrap items-center gap-2">
                <button type="button" @click="prevDay()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm transition hover:bg-gray-50" aria-label="Dia anterior">‹</button>
                <div class="flex items-center gap-1">
                    <template x-for="d in dayDates" :key="d.key">
                        <button type="button" @click="goToDate(d.date)" :class="d.active ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-50'" class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-center leading-tight transition">
                            <span class="block text-[10px] uppercase tracking-wide opacity-70" x-text="d.weekday"></span>
                            <span class="block text-xs font-semibold" x-text="d.dayMonth"></span>
                        </button>
                    </template>
                </div>
                <button type="button" @click="nextDay()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm transition hover:bg-gray-50" aria-label="Próximo dia">›</button>
                <span class="ml-2 text-sm font-medium text-gray-700" x-text="periodLabel"></span>
            </div>

            <div x-show="view === 'week'" class="flex flex-wrap items-center gap-2">
                <button type="button" @click="prevWeek()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm transition hover:bg-gray-50">‹ Semana Anterior</button>
                <button type="button" @click="currentWeek()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">Semana Atual</button>
                <button type="button" @click="nextWeek()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm transition hover:bg-gray-50">Próxima Semana ›</button>
                <span class="ml-2 text-sm font-medium text-gray-700" x-text="periodLabel"></span>
            </div>

            <button type="button" @click="toggleFullscreen()" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50" :aria-label="isFullscreen ? 'Sair da tela cheia' : 'Tela cheia'" :title="isFullscreen ? 'Sair da tela cheia' : 'Tela cheia'">
                <svg x-show="!isFullscreen" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                </svg>
                <svg x-show="isFullscreen" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5M15 15l5.25 5.25" />
                </svg>
            </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="p-4">
                <div id="dayView" class="agenda-view"></div>
                <div id="weekView" class="agenda-view hidden"></div>
            </div>
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

    <!-- Modal: Reagendar (movimentação horizontal) -->
    <div x-show="moveModal.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="cancelMove()">
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl" @click.outside="cancelMove()">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-800">Reagendar agendamento</h3>
                <button type="button" @click="cancelMove()" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4 p-6">
                <div class="rounded-lg bg-blue-50 px-4 py-3">
                    <p class="text-sm font-semibold text-blue-800" x-text="moveModal.patientName || '—'"></p>
                    <p class="mt-1 text-xs text-blue-700" x-show="moveModal.professionalName" x-text="moveModal.professionalName"></p>
                </div>

                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wide text-gray-400">Nova data</span>
                    <p class="mt-1 text-sm font-medium text-gray-800" x-text="moveModal.dateLabel || '—'"></p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="move_start_time" class="mb-1 block text-sm font-medium text-gray-700">Hora de início *</label>
                        <input type="time" id="move_start_time" x-model="moveModal.startTime" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label for="move_end_time" class="mb-1 block text-sm font-medium text-gray-700">Hora de fim *</label>
                        <input type="time" id="move_end_time" x-model="moveModal.endTime" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    </div>
                </div>

                <p class="text-xs text-gray-500">Confirme os horários para reagendar. Os dados atuais já vêm preenchidos.</p>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                <button type="button" @click="cancelMove()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</button>
                <button type="button" @click="confirmMove()" :disabled="moveModal.saving" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50" x-text="moveModal.saving ? 'Salvando...' : 'Confirmar'"></button>
            </div>
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
    <!-- Modal: Detalhes do Agendamento -->
    <div x-show="selected.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="selected.open = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl" @click.outside="selected.open = false">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-800">Agendamento</h3>
                <button type="button" @click="selected.open = false" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="space-y-4 p-6">
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wide text-gray-400">Paciente</span>
                    <p class="mt-1 text-sm font-medium text-gray-800" x-text="selected.event ? selected.event.title : '—'"></p>
                </div>
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wide text-gray-400">Quando</span>
                    <p class="mt-1 text-sm text-gray-700" x-text="selected.event ? selected.event.when : '—'"></p>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 pt-4">
                    <button type="button" @click="selected.open = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Fechar</button>
                    <a :href="selected.event ? (evolutionCreateUrl + '/' + selected.event.id) : '#'" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Registrar Evolução</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast de feedback -->
    <div
        x-show="toast.open"
        x-transition.opacity.duration.200ms
        x-cloak
        :class="toast.type === 'error' ? 'bg-red-600' : 'bg-green-600'"
        class="fixed bottom-6 right-6 z-[60] flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg"
        role="status"
        aria-live="polite"
    >
        <svg x-show="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        <svg x-show="toast.type === 'error'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span x-text="toast.message"></span>
    </div>
</div>

<script>
<?php
$agendaProfessionals = [];
foreach ($professionals as $professional) {
    $agendaProfessionals[] = [
        'id'   => (int) $professional['id'],
        'name' => $professional['name'],
    ];
}
?>
function agendaApp() {
    const professionals = <?= json_encode($agendaProfessionals) ?>;
    let dayCalendar = null;
    let weekScheduler = null;
    let pendingMoveResolve = null;
    let pendingMoveSnapshot = null;
    let horizontalIntercepted = false;

    function buildColumns() {
        return professionals.map((p) => ({ id: String(p.id), name: p.name }));
    }

    function buildResources() {
        return professionals.map((p) => ({ id: String(p.id), name: p.name }));
    }

    function toJsDate(date) {
        // DayPilot.Date.getMonth() já retorna o mês zero-based (0-11), igual ao
        // construtor de Date do JavaScript. Subtrair 1 aqui deslocava a data um
        // mês para trás (ex.: um card solto em 29/10 virava 29/09).
        return new Date(date.getYear(), date.getMonth(), date.getDay(), date.getHours(), date.getMinutes(), date.getSeconds());
    }

    function iso(date) {
        return date.toString();
    }

    function isoDate(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');

        return y + '-' + m + '-' + day;
    }

    function addDaysIso(isoStr, days) {
        const d = new Date(isoStr + 'T00:00:00');
        d.setDate(d.getDate() + days);

        return isoDate(d);
    }

    function todayIso() {
        return isoDate(new Date());
    }

    function weekStartIso(isoStr) {
        const d = new Date(isoStr + 'T00:00:00');
        d.setDate(d.getDate() - d.getDay());

        return isoDate(d);
    }

    return {
        open: false,
        submitting: false,
        conflict: { open: false, conflicts: [] },
        pendingPayload: null,
        selected: { open: false, event: null },
        moveModal: {
            open: false,
            saving: false,
            patientName: '',
            professionalName: '',
            date: '',
            dateLabel: '',
            startTime: '',
            endTime: '',
            professionalId: null,
        },
        toast: { open: false, message: '', type: 'success' },
        toastTimer: null,
        isFullscreen: false,
        view: 'day',
        dateStr: <?= json_encode($date) ?>,
        evolutionCreateUrl: <?= json_encode(site_url('evolutions/create')) ?>,
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
        init() {
            window.addEventListener('agenda:eventClick', (event) => {
                this.selected.event = event.detail;
                this.selected.open = true;
            });

            window.addEventListener('resize', () => {
                if (this.view === 'week') {
                    this.fitWeekScheduler();
                }
            });

            document.addEventListener('fullscreenchange', () => this.onFullscreenChange());
            document.addEventListener('webkitfullscreenchange', () => this.onFullscreenChange());

            if (typeof DayPilot === 'undefined') {
                return;
            }

            this.setView('day');
        },
        get periodLabel() {
            if (this.view === 'day') {
                return new Date(this.dateStr + 'T00:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            }

            const start = weekStartIso(this.dateStr);
            const end = addDaysIso(start, 6);

            return new Date(start + 'T00:00:00').toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' })
                + ' – '
                + new Date(end + 'T00:00:00').toLocaleDateString('pt-BR', { day: 'numeric', month: 'short', year: 'numeric' });
        },
        get dayDates() {
            const today = new Date();
            const list = [];

            for (let i = 0; i < 10; i++) {
                const d = new Date(today);
                d.setDate(d.getDate() + i);
                const isoStr = isoDate(d);

                list.push({
                    key: isoStr,
                    date: isoStr,
                    weekday: d.toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', ''),
                    dayMonth: d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }),
                    active: isoStr === this.dateStr,
                });
            }

            return list;
        },
        get dayButtonClass() {
            return this.view === 'day'
                ? 'rounded-lg px-4 py-2 text-sm font-semibold bg-blue-600 text-white shadow-sm transition'
                : 'rounded-lg px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 transition';
        },
        get weekButtonClass() {
            return this.view === 'week'
                ? 'rounded-lg px-4 py-2 text-sm font-semibold bg-blue-600 text-white shadow-sm transition'
                : 'rounded-lg px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 transition';
        },
        setView(view) {
            const active = view === 'day' ? dayCalendar : weekScheduler;

            if (this.view === view && active) {
                return;
            }

            this.view = view;
            this.disposeCalendars();
            this.showCalendarContainer(view);

            if (view === 'day') {
                this.initDayCalendar();
            } else {
                this.initWeekScheduler();
            }

            this.refresh();
        },
        prevDay() {
            this.dateStr = addDaysIso(this.dateStr, -1);
            this.refresh();
        },
        nextDay() {
            this.dateStr = addDaysIso(this.dateStr, 1);
            this.refresh();
        },
        goToDate(isoStr) {
            this.dateStr = isoStr;
            this.refresh();
        },
        prevWeek() {
            this.dateStr = addDaysIso(this.dateStr, -7);
            this.refresh();
        },
        currentWeek() {
            this.dateStr = todayIso();
            this.refresh();
        },
        nextWeek() {
            this.dateStr = addDaysIso(this.dateStr, 7);
            this.refresh();
        },
        async refresh() {
            const range = this.fetchRange();
            const events = await this.fetchEvents(range.start, range.end);

            console.log('[Agenda] refresh:', { view: this.view, range, count: events.length, events });

            if (this.view === 'day' && dayCalendar) {
                dayCalendar.update({ startDate: this.dateStr, columns: buildColumns(), events });
            } else if (this.view === 'week' && weekScheduler) {
                weekScheduler.update({ startDate: weekStartIso(this.dateStr), resources: buildResources(), events });
            }
        },
        fetchRange() {
            if (this.view === 'day') {
                return {
                    start: this.dateStr + 'T00:00:00',
                    end: addDaysIso(this.dateStr, 1) + 'T00:00:00',
                };
            }

            const start = weekStartIso(this.dateStr);

            return {
                start: start + 'T00:00:00',
                end: addDaysIso(start, 7) + 'T00:00:00',
            };
        },
        async fetchEvents(start, end) {
            const params = new URLSearchParams({ start, end });
            const url = <?= json_encode(site_url('agenda/events')) ?> + '?' + params.toString();
            const response = await fetch(url);

            if (! response.ok) {
                alert('Falha ao carregar os agendamentos.');
                return [];
            }

            const events = await response.json();

            console.log('[Agenda] fetchEvents:', { start, end, count: events.length, events });

            return events;
        },
        initDayCalendar() {
            dayCalendar = new DayPilot.Calendar('dayView', {
                viewType: 'Resources',
                locale: 'pt-br',
                heightSpec: 'Full',
                startDate: this.dateStr,
                businessBeginsHour: 0,
                businessEndsHour: 24,
                cellDuration: 30,
                columns: buildColumns(),
                eventMoveHandling: 'Update',
                eventResizeHandling: 'Disabled',
                timeRangeSelectedHandling: 'Disabled',
                onEventClick: (args) => this.handleEventClick(args.e),
                onEventMove: (args) => this.handleEventMove(args),
                onEventMoved: (args) => this.handleEventMoved(args),
                onBeforeCellRender: (args) => this.applyZebra(args),
                onBeforeEventRender: (args) => this.applyEventStyle(args),
            });
            dayCalendar.init();
        },
        initWeekScheduler() {
            weekScheduler = new DayPilot.Scheduler('weekView', {
                locale: 'pt-br',
                scale: 'Day',
                days: 7,
                weekStarts: 0,
                businessBeginsHour: 0,
                businessEndsHour: 24,
                businessWeekends: true,
                heightSpec: 'Auto',
                width: '100%',
                startDate: weekStartIso(this.dateStr),
                timeHeaders: [
                    { groupBy: 'Day', format: 'dddd' },
                    { groupBy: 'Day', format: 'dd/MM' },
                ],
                resources: buildResources(),
                cellWidth: 100,
                rowHeaderWidth: 120,
                rowHeight: 64,
                eventHeight: 44,
                eventMoveHandling: 'Update',
                eventResizeHandling: 'Disabled',
                timeRangeSelectedHandling: 'Disabled',
                onEventClick: (args) => this.handleEventClick(args.e),
                onEventMove: (args) => this.handleEventMove(args),
                onEventMoved: (args) => this.handleEventMoved(args),
                onBeforeCellRender: (args) => this.applyZebra(args),
                onBeforeEventRender: (args) => this.applyEventStyle(args),
            });
            weekScheduler.init();
            this.fitWeekScheduler();
        },
        fitWeekScheduler() {
            if (! weekScheduler || this.view !== 'week') {
                return;
            }

            const container = document.getElementById('weekView');

            if (! container || container.clientWidth === 0) {
                return;
            }

            const rowHeaderWidth = weekScheduler.rowHeaderWidth || 120;
            const available = Math.max(0, container.clientWidth - rowHeaderWidth);
            const cellWidth = Math.max(80, Math.floor(available / 7) - 2);

            weekScheduler.update({ cellWidth });
        },
        applyZebra(args) {
            // Garante que todas as células sejam tratadas como "horário comercial"
            // (24/7), eliminando o cinza nativo de madrugada e fim de semana.
            args.cell.properties.business = true;

            const resourceId = args.cell.resource;
            const index = professionals.findIndex((p) => String(p.id) === String(resourceId));

            if (index < 0) {
                return;
            }

            const isEven = (index % 2 === 0);

            // O DayPilot Lite (6.x) expõe as propriedades de renderização em
            // `args.cell.properties` (e não em `args.cell` diretamente).
            args.cell.properties.cssClass = isEven ? 'zebra-even' : 'zebra-odd';
            args.cell.properties.backColor = isEven ? '#ffffff' : '#f8f9fa';
        },
        applyEventStyle(args) {
            const name = DayPilot.Util.escapeHtml(args.data.text || 'Paciente');
            const start = args.data.start.toString('HH:mm');
            const end = args.data.end.toString('HH:mm');

            const timeString = start + ' - ' + end;

            args.data.html = ''
                + '<div style="display:flex;flex-direction:column;justify-content:center;height:100%;line-height:1.2;padding:2px 4px;overflow:hidden;">'
                + '<div style="font-weight:bold;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + name + '</div>'
                + '<div style="font-size:10px;opacity:.85;font-weight:normal;white-space:nowrap;">' + timeString + '</div>'
                + '</div>';
            args.data.cssClass = 'agenda-event-card';
        },
        disposeCalendars() {
            if (dayCalendar) {
                dayCalendar.dispose();
                dayCalendar = null;
            }

            if (weekScheduler) {
                weekScheduler.dispose();
                weekScheduler = null;
            }
        },
        showCalendarContainer(view) {
            document.getElementById('dayView').classList.toggle('hidden', view !== 'day');
            document.getElementById('weekView').classList.toggle('hidden', view !== 'week');
        },
        openModal() {
            this.form.end_time = this.addMinutes(this.form.start_time, this.defaultDuration);
            this.open = true;
        },
        toggleFullscreen() {
            const el = this.$refs.fullscreenContainer;
            const isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement);

            if (!isFullscreen) {
                const request = el.requestFullscreen || el.webkitRequestFullscreen;

                if (request) {
                    const result = request.call(el);

                    if (result && typeof result.catch === 'function') {
                        result.catch(() => {});
                    }
                }

                return;
            }

            const exit = document.exitFullscreen || document.webkitExitFullscreen;

            if (exit) {
                const result = exit.call(document);

                if (result && typeof result.catch === 'function') {
                    result.catch(() => {});
                }
            }
        },
        onFullscreenChange() {
            this.isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement);
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
        handleEventClick(e) {
            const formatDate = (date) => {
                if (! date) {
                    return '';
                }

                const js = toJsDate(date);

                return js.toLocaleDateString('pt-BR') + ' ' + js.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            };

            window.dispatchEvent(new CustomEvent('agenda:eventClick', {
                detail: {
                    id: e.data.id,
                    title: e.data.text || 'Paciente',
                    when: formatDate(e.start()) + (e.end() ? ' até ' + formatDate(e.end()) : ''),
                },
            }));
        },
        handleEventMove(args) {
            // Visão Dia: drag-and-drop livre e direto. Não interceptamos nada
            // aqui; a ação padrão move o card e o onEventMoved salva em background.
            if (this.view === 'day') {
                return;
            }

            // Visão Semana: onEventMove dispara ANTES da ação padrão. No DayPilot
            // Lite o Calendar (visão Dia) não expõe args.async/args.loaded, então a
            // decisão precisa ser síncrona:
            // - Mesmo dia e mesmo profissional (só a hora mudou): não intervém;
            //   a ação padrão move o card e o onEventMoved salva em background.
            // - Troca de dia e/ou de profissional: reverte na hora com
            //   preventDefault() e abre o modal para confirmar os horários.
            if (this.isVerticalOnly(args)) {
                return;
            }

            // Valores originais (antes do arrasto) usados para manter o card
            // visível na posição de origem enquanto o modal está aberto.
            const originalStart = args.e.start();
            const originalEnd = args.e.end ? args.e.end() : null;
            const originalResource = args.e.data.resource != null ? args.e.data.resource : null;

            const newStart = args.newStart || originalStart;
            const newEnd = args.newEnd || originalEnd;

            const newResource = args.newResource != null
                ? args.newResource
                : originalResource;
            const professionalId = newResource != null ? Number(newResource) : null;
            const professional = professionals.find((p) => Number(p.id) === professionalId);

            const snapshot = {
                id: Number(args.e.data.id),
                patientName: args.e.data.text || 'Paciente',
                date: isoDate(toJsDate(newStart)),
                startTime: newStart.toString('HH:mm'),
                endTime: newEnd ? newEnd.toString('HH:mm') : this.addMinutes(newStart.toString('HH:mm'), this.defaultDuration),
                professionalId,
                professionalName: professional ? professional.name : '',
            };

            console.log('[Agenda] handleEventMove (week):', {
                newStartRaw: newStart.toString(),
                newEndRaw: newEnd ? newEnd.toString() : null,
                computedDate: snapshot.date,
                snapshot,
            });

            args.preventDefault();

            // O preventDefault() impede a atualização dos dados, mas no
            // DayPilot Lite o card pode não ser revertido visualmente. Força a
            // restauração dos valores originais para mantê-lo no lugar.
            args.e.data.start = originalStart;
            args.e.data.end = originalEnd;
            args.e.data.resource = originalResource;

            this.startHorizontalMove(snapshot).catch(() => {});
        },
        isVerticalOnly(args) {
            const originalDay = isoDate(toJsDate(args.e.start()));
            const newDay = isoDate(toJsDate(args.newStart));

            const originalResource = args.e.data.resource != null
                ? String(args.e.data.resource)
                : '';
            const newResource = args.newResource != null
                ? String(args.newResource)
                : originalResource;

            return originalDay === newDay && originalResource === newResource;
        },
        async startHorizontalMove(snapshot) {
            // Aguarda o usuário confirmar ou cancelar o reagendamento. A
            // re-renderização dos eventos é disparada por confirmMove() e
            // cancelMove() após o modal ser fechado.
            await this.openMoveModal(snapshot);
        },
        openMoveModal(snapshot) {
            // Garante que um modal anterior não fique pendente.
            this.resolveMove(false);
            horizontalIntercepted = true;

            return new Promise((resolve) => {
                pendingMoveSnapshot = snapshot;
                pendingMoveResolve = resolve;

                this.moveModal.patientName = snapshot.patientName;
                this.moveModal.professionalName = snapshot.professionalName;
                this.moveModal.date = snapshot.date;
                this.moveModal.dateLabel = this.formatMoveDate(snapshot.date);
                this.moveModal.startTime = snapshot.startTime;
                this.moveModal.endTime = snapshot.endTime;
                this.moveModal.professionalId = snapshot.professionalId;
                this.moveModal.saving = false;
                this.moveModal.open = true;
            });
        },
        formatMoveDate(isoStr) {
            if (! isoStr) {
                return '';
            }

            return new Date(isoStr + 'T00:00:00').toLocaleDateString('pt-BR', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
            });
        },
        buildModalPayload() {
            if (! pendingMoveSnapshot) {
                return null;
            }

            const date = this.moveModal.date;
            const startTime = this.moveModal.startTime;
            const endTime = this.moveModal.endTime;

            if (! date || ! startTime || ! endTime) {
                alert('Informe a data e os horários de início e fim.');
                return null;
            }

            if (endTime <= startTime) {
                alert('O horário de fim deve ser posterior ao horário de início.');
                return null;
            }

            const payload = {
                id: Number(pendingMoveSnapshot.id),
                start: date + ' ' + startTime + ':00',
                end: date + ' ' + endTime + ':00',
            };

            const professionalId = this.moveModal.professionalId != null
                ? this.moveModal.professionalId
                : pendingMoveSnapshot.professionalId;

            if (professionalId != null && professionalId !== '') {
                payload.professional_id = Number(professionalId);
            }

            return payload;
        },
        async confirmMove() {
            if (this.moveModal.saving) {
                return;
            }

            const payload = this.buildModalPayload();

            if (! payload) {
                // buildModalPayload já exibiu a mensagem de validação.
                return;
            }

            console.log('[Agenda] confirmMove payload:', payload);

            this.moveModal.saving = true;
            const outcome = await this.persistMove(payload);
            this.moveModal.saving = false;

            if (outcome.ok) {
                this.moveModal.open = false;
                this.resolveMove(true);

                console.log('[Agenda] confirmMove salvo:', { payload, event: outcome.event });

                // Atualiza o card manualmente na nova posição. Se o backend não
                // devolveu o evento atualizado (ou o card não está mais na lista),
                // cai no refresh completo como fallback.
                if (! this.applyEventUpdate(outcome.event)) {
                    this.refresh().catch(() => {});
                }

                return;
            }

            if (outcome.keepOpen) {
                // Erro de validação do backend: mantém o modal aberto.
                return;
            }

            this.moveModal.open = false;
            this.resolveMove(false);
            // A movimentação não foi salva: restaura o card à posição original.
            this.refresh().catch(() => {});
        },
        cancelMove() {
            if (this.moveModal.saving) {
                return;
            }

            this.moveModal.open = false;
            this.resolveMove(false);
            // Garante que o card volte à posição original após cancelar.
            this.refresh().catch(() => {});
        },
        resolveMove(confirmed) {
            if (! pendingMoveResolve) {
                return;
            }

            const resolve = pendingMoveResolve;
            pendingMoveResolve = null;
            pendingMoveSnapshot = null;
            horizontalIntercepted = false;
            resolve(confirmed);
        },
        async persistMove(payload) {
            try {
                const result = await this.postUpdate(payload);
                this.updateCsrf(result.data);

                if (result.status === 409) {
                    const proceed = confirm('Conflito de horário para o profissional. Deseja continuar mesmo assim?');

                    if (! proceed) {
                        return { ok: false, keepOpen: false };
                    }

                    const forced = await this.postUpdate({ ...payload, force_override: true });
                    this.updateCsrf(forced.data);

                    if (forced.status !== 200) {
                        alert(forced.data.message || 'Não foi possível mover o agendamento.');
                        return { ok: false, keepOpen: false };
                    }

                    return { ok: true, event: forced.data.event || null };
                }

                if (result.status !== 200) {
                    alert(result.data.message || 'Não foi possível mover o agendamento.');
                    return { ok: false, keepOpen: result.status === 422 };
                }

                return { ok: true, event: result.data.event || null };
            } catch (error) {
                alert('Erro ao mover o agendamento.');
                return { ok: false, keepOpen: false };
            }
        },
        applyEventUpdate(event) {
            if (! event || ! event.id) {
                return false;
            }

            const id = String(event.id);
            const numericId = Number(event.id);
            const findEvent = (scheduler) => scheduler.events.find(id) || scheduler.events.find(numericId);
            const start = new DayPilot.Date(event.start);
            const end = new DayPilot.Date(event.end);

            if (this.view === 'week' && weekScheduler) {
                const existing = findEvent(weekScheduler);

                if (existing) {
                    existing.data.start = start;
                    existing.data.end = end;
                    existing.data.resource = event.resource != null ? String(event.resource) : existing.data.resource;
                    weekScheduler.events.update(existing);

                    console.log('[Agenda] applyEventUpdate (week):', { id, start: event.start, end: event.end, resource: event.resource });

                    return true;
                }
            }

            if (this.view === 'day' && dayCalendar) {
                const existing = findEvent(dayCalendar);

                if (existing) {
                    existing.data.start = start;
                    existing.data.end = end;
                    existing.data.resource = event.resource != null ? String(event.resource) : existing.data.resource;
                    dayCalendar.events.update(existing);

                    console.log('[Agenda] applyEventUpdate (day):', { id, start: event.start, end: event.end, resource: event.resource });

                    return true;
                }
            }

            return false;
        },
        async handleEventMoved(args) {
            // Rede de segurança: movimentos horizontais são revertidos em
            // onEventMove e tratados pelo modal; não devem ser salvos aqui.
            if (horizontalIntercepted) {
                horizontalIntercepted = false;
                return;
            }

            const payload = {
                id: Number(args.e.data.id),
                start: iso(args.newStart),
                end: iso(args.newEnd),
            };

            const newResource = args.newResource != null
                ? args.newResource
                : (args.e && args.e.data ? args.e.data.resource : null);

            if (newResource != null && newResource !== '') {
                payload.professional_id = Number(newResource);
            }

            console.log('[Agenda] handleEventMoved:', { view: this.view, payload, newResource });

            try {
                const result = await this.postUpdate(payload);
                this.updateCsrf(result.data);

                if (result.status === 409) {
                    const proceed = confirm('Conflito de horário para o profissional. Deseja continuar mesmo assim?');

                    if (! proceed) {
                        await this.refresh();
                        return;
                    }

                    const forced = await this.postUpdate({ ...payload, force_override: true });
                    this.updateCsrf(forced.data);

                    if (forced.status !== 200) {
                        alert(forced.data.message || 'Não foi possível mover o agendamento.');
                        await this.refresh();
                    }

                    return;
                }

                if (result.status !== 200) {
                    alert(result.data.message || 'Não foi possível mover o agendamento.');
                    await this.refresh();
                }
            } catch (error) {
                alert('Erro ao mover o agendamento.');
                await this.refresh();
            }
        },
        updateCsrf(data) {
            if (data && data.csrf_hash) {
                this.token = data.csrf_hash;
            }
        },
        async postUpdate(payload) {
            const response = await fetch(<?= json_encode(site_url('agenda/update-schedule')) ?>, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.token,
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
        },
        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.open = true;

            clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => { this.toast.open = false; }, 2500);
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


<?= $this->endSection() ?>

