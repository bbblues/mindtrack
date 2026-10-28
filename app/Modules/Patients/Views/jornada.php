<div x-show="tab === 'jornada'">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-gray-700">Jornada do Paciente</h3>
            <p class="text-xs text-gray-500">Marcos e conquistas ao longo do tratamento.</p>
        </div>
        <button
            type="button"
            @click="milestoneOpen = !milestoneOpen"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span x-text="milestoneOpen ? 'Fechar' : 'Adicionar marco'"></span>
        </button>
    </div>

    <form x-show="milestoneOpen" x-cloak @submit.prevent="submitMilestone()" class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="milestone-title" class="mb-1 block text-sm font-medium text-gray-700">Título *</label>
                <input
                    id="milestone-title"
                    type="text"
                    x-model="milestoneTitle"
                    placeholder="Ex: Início da Fonoaudiologia"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                >
            </div>
            <div>
                <label for="milestone-date" class="mb-1 block text-sm font-medium text-gray-700">Data *</label>
                <input
                    id="milestone-date"
                    type="date"
                    x-model="milestoneEventDate"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                >
            </div>
            <div>
                <label for="milestone-icon" class="mb-1 block text-sm font-medium text-gray-700">Ícone</label>
                <select
                    id="milestone-icon"
                    x-model="milestoneIcon"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                >
                    <template x-for="[value, label] in Object.entries(milestoneIcons)" :key="value">
                        <option :value="value" x-text="value + ' ' + label"></option>
                    </template>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="milestone-description" class="mb-1 block text-sm font-medium text-gray-700">Descrição</label>
                <textarea
                    id="milestone-description"
                    x-model="milestoneDescription"
                    rows="2"
                    placeholder="Detalhes opcionais sobre este marco..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                ></textarea>
            </div>
        </div>

        <div class="mt-3 flex justify-end gap-2">
            <button type="button" @click="milestoneOpen = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancelar</button>
            <button type="submit" :disabled="milestoneSaving" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60">
                <span x-show="!milestoneSaving">Salvar marco</span>
                <span x-show="milestoneSaving">Salvando...</span>
            </button>
        </div>

        <p x-show="milestoneError" x-cloak x-text="milestoneError" class="mt-2 text-sm text-red-600"></p>
    </form>

    <div x-show="milestones.length === 0" class="flex flex-col items-center justify-center px-6 py-12 text-center">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
        </div>
        <p class="text-sm font-medium text-gray-700">Nenhum marco registrado</p>
        <p class="mt-1 text-sm text-gray-500">Adicione o primeiro marco para montar a linha do tempo da jornada.</p>
    </div>

    <ol x-show="milestones.length > 0" class="relative space-y-8 border-l border-gray-200 pl-6">
        <template x-for="group in milestoneGroups" :key="group.key">
            <li class="list-none">
                <h4 class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span x-text="group.label"></span>
                </h4>

                <ol class="space-y-6">
                    <template x-for="milestone in group.items" :key="milestone.id">
                        <li class="relative">
                            <span class="absolute -left-[31px] top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 ring-4 ring-white"></span>

                            <div class="flex items-center gap-2">
                                <span x-show="milestone.icon" x-text="milestone.icon" class="text-lg"></span>
                                <p class="text-sm font-semibold text-gray-800" x-text="milestone.title"></p>
                            </div>
                            <p x-show="milestone.description" x-text="milestone.description" class="mt-1 whitespace-pre-line text-sm text-gray-600"></p>
                            <p class="mt-1 text-xs text-gray-400" x-text="formatDay(milestone.event_date)"></p>
                        </li>
                    </template>
                </ol>
            </li>
        </template>
    </ol>
</div>
