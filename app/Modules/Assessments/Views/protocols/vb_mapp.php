<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$finalized = ($assessment['status'] ?? '') === 'completed';

$scores = $scores ?? [];

$bySection = [];
foreach (($scores['by_section'] ?? []) as $sectionRow) {
    $bySection[(string) $sectionRow['section']] = $sectionRow;
}

$itemValues = [];
foreach ($levels as $level) {
    foreach ($level['skills'] as $skill) {
        foreach ($skill['items'] as $item) {
            if ($item['assessment_item_id'] === null) {
                continue;
            }

            $itemValues[(string) $item['assessment_item_id']] = (float) $item['score_achieved'];
        }
    }
}

$typeOf = static function (string $section, string $name): string {
    $label = mb_strtolower($section !== '' ? $section : $name, 'UTF-8');

    if (str_contains($label, 'barreira')) {
        return 'barriers';
    }

    if (str_contains($label, 'transi')) {
        return 'transition';
    }

    if (str_contains($label, 'marcador')) {
        return 'milestones';
    }

    if (str_contains($label, 'rastreamento')) {
        return 'tracking';
    }

    return 'list';
};

$sectionSummaries = [];
foreach ($levels as $level) {
    $sectionKey = $level['section'] !== '' ? $level['section'] : $level['name'];
    $aggregate  = $bySection[$sectionKey] ?? ['score' => 0.0, 'max' => 0.0, 'percentage' => 0.0];

    $sectionSummaries[] = [
        'key'        => $sectionKey,
        'label'      => $level['section'] !== '' ? $level['section'] : $level['name'],
        'score'      => (float) ($aggregate['score'] ?? 0),
        'max'        => (float) ($aggregate['max'] ?? 0),
        'percentage' => (float) ($aggregate['percentage'] ?? 0),
    ];
}
?>

<style>[x-cloak] { display: none !important; }</style>

<div x-data="vbMappApp()" x-cloak class="space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4 p-6">
            <div>
                <a href="<?= site_url('patients') ?>" class="mb-3 inline-flex items-center gap-1 text-sm font-medium text-blue-600 transition hover:text-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Pacientes
                </a>
                <h2 class="text-xl font-semibold text-gray-800"><?= esc($patient['name']) ?></h2>
                <p class="mt-1 text-sm text-gray-500"><?= esc($protocol['name']) ?> · VB-MAPP</p>
                <span
                    class="mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                    :class="status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                    x-text="status === 'completed' ? 'Finalizada' : 'Em Andamento'"
                ></span>
            </div>

            <div class="flex flex-col items-end gap-3">
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-500">Score total</p>
                    <p class="text-4xl font-bold text-blue-600">
                        <span x-text="totalScore"></span><span class="ml-1 text-lg font-semibold text-gray-400">/ <span x-text="totalPossible"></span></span>
                    </p>
                    <p class="text-xs text-gray-500"><span x-text="percentage"></span>%</p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <button
                        type="button"
                        x-show="!finalized"
                        @click="finalize()"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >
                        Finalizar Avaliação
                    </button>
                    <button
                        type="button"
                        x-show="finalized"
                        @click="exportPdf()"
                        :disabled="exporting"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <svg x-show="exporting" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="exporting ? 'Gerando PDF...' : 'Exportar PDF'"></span>
                    </button>
                    <button
                        type="button"
                        x-show="finalized"
                        @click="generatePlan()"
                        :disabled="generatingPlan"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <svg x-show="generatingPlan" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="generatingPlan ? 'Gerando...' : 'Gerar Plano Terapêutico'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php if (session('success')): ?>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= esc(session('success')) ?></div>
    <?php endif; ?>

    <?php if (session('error')): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <?php if ($sectionSummaries === []): ?>
        <div class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Este protocolo ainda não possui itens</h3>
            <p class="mt-1 max-w-sm text-sm text-gray-500">Importe o protocolo VB-MAPP na área de Protocolos para começar a avaliar.</p>
            <a href="<?= site_url('protocols') ?>" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Ir para Protocolos</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <template x-for="key in sectionKeys" :key="key">
                <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                    <p class="truncate text-xs font-medium text-gray-500" x-text="sections[key].label"></p>
                    <p class="mt-1 text-lg font-bold text-blue-600">
                        <span x-text="sections[key].score"></span><span class="text-xs font-semibold text-gray-400"> / <span x-text="sections[key].max"></span></span>
                    </p>
                    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-blue-500" :style="'width: ' + Math.min(100, Number(sections[key].percentage) || 0) + '%'"></div>
                    </div>
                </div>
            </template>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex overflow-x-auto border-b border-gray-200">
                <template x-for="key in sectionKeys" :key="key">
                    <button
                        type="button"
                        @click="activeTab = key"
                        :class="activeTab === key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold transition"
                    >
                        <span x-text="sections[key].label"></span>
                    </button>
                </template>
            </div>

            <?php foreach ($levels as $level): ?>
                <?php
                $sectionKey  = $level['section'] !== '' ? $level['section'] : $level['name'];
                $sectionType = $typeOf($level['section'], $level['name']);
                ?>
                <div x-show="activeTab === <?= esc(json_encode($sectionKey), 'attr') ?>" class="p-4">
                    <?= view('App\Modules\Assessments\Views\protocols\_vb_mapp_panel', [
                        'level' => $level,
                        'type'  => $sectionType,
                    ]) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div
        x-show="toastVisible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-6 right-6 z-50 rounded-lg bg-green-600 px-4 py-3 text-sm font-semibold text-white shadow-lg"
        x-cloak
        x-text="toastMessage"
    ></div>
</div>

<script>
function vbMappApp() {
    const sectionList = <?= json_encode($sectionSummaries, JSON_UNESCAPED_UNICODE) ?>;
    const sections = {};

    sectionList.forEach((section) => {
        sections[section.key] = Object.assign({}, section);
    });

    const sectionKeys = sectionList.map((section) => section.key);

    return {
        sections: sections,
        sectionKeys: sectionKeys,
        activeTab: sectionKeys.length ? sectionKeys[0] : '',
        values: <?= json_encode((object) $itemValues) ?>,
        csrfToken: <?= json_encode(csrf_hash()) ?>,
        finalized: <?= $finalized ? 'true' : 'false' ?>,
        status: <?= $finalized ? "'completed'" : "'in_progress'" ?>,
        totalScore: <?= (float) ($scores['total_score'] ?? 0) ?>,
        totalPossible: <?= (float) ($scores['total_possible'] ?? 0) ?>,
        percentage: <?= (float) ($scores['percentage'] ?? 0) ?>,
        updateUrl: <?= json_encode(site_url('assessments/update-item')) ?>,
        finalizeUrl: <?= json_encode(site_url('assessments/finalize/' . (int) $assessment['id'])) ?>,
        generatePlanUrl: <?= json_encode(site_url('plans/store')) ?>,
        patientId: <?= (int) $assessment['patient_id'] ?>,
        assessmentId: <?= (int) $assessment['id'] ?>,
        exportPdfUrl: <?= json_encode(site_url('assessments/export-pdf/' . (int) $assessment['id'])) ?>,
        exporting: false,
        generatingPlan: false,
        toastVisible: false,
        toastMessage: '',
        toastTimer: null,

        showToast(message) {
            this.toastMessage = message;
            this.toastVisible = true;

            if (this.toastTimer) {
                clearTimeout(this.toastTimer);
            }

            this.toastTimer = setTimeout(() => {
                this.toastVisible = false;
            }, 2000);
        },

        async sendItem(itemId, score) {
            try {
                const response = await fetch(this.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ assessment_item_id: itemId, score_achieved: score }),
                });

                const data = await response.json();

                if (data.csrf_hash) {
                    this.csrfToken = data.csrf_hash;
                }

                if (! data.success) {
                    alert(data.message || 'Erro ao salvar o item.');
                    return false;
                }

                this.totalScore = data.score;
                this.totalPossible = data.total_possible;
                this.percentage = data.percentage;

                (data.by_section || []).forEach((row) => {
                    if (this.sections[row.section]) {
                        this.sections[row.section].score = row.score;
                        this.sections[row.section].max = row.max;
                        this.sections[row.section].percentage = row.percentage;
                    }
                });

                this.showToast('Item salvo');

                return true;
            } catch (error) {
                alert('Erro ao salvar o item.');
                return false;
            }
        },

        async toggle(itemId) {
            if (this.finalized) {
                return;
            }

            const current = Number(this.values[itemId] || 0);
            const next = current > 0 ? 0 : 1;

            this.values[itemId] = next;

            const saved = await this.sendItem(itemId, next);

            if (! saved) {
                this.values[itemId] = current;
            }
        },

        async saveNumber(itemId, input) {
            if (this.finalized) {
                return;
            }

            let value = Number(input.value);
            const max = Number(input.max || 0);

            if (Number.isNaN(value)) {
                value = 0;
            }

            value = Math.round(value);
            value = Math.min(Math.max(value, 0), max);
            input.value = value;

            const previous = Number(this.values[itemId] || 0);
            this.values[itemId] = value;

            const saved = await this.sendItem(itemId, value);

            if (! saved) {
                this.values[itemId] = previous;
                input.value = previous;
            }
        },

        async finalize() {
            if (! confirm('Finalizar esta avaliação? Após finalizar, a edição será bloqueada.')) {
                return;
            }

            try {
                const response = await fetch(this.finalizeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({}),
                });

                const data = await response.json();

                if (data.csrf_hash) {
                    this.csrfToken = data.csrf_hash;
                }

                if (! data.success) {
                    alert(data.message || 'Erro ao finalizar a avaliação.');
                    return;
                }

                this.finalized = true;
                this.status = 'completed';
            } catch (error) {
                alert('Erro ao finalizar a avaliação.');
            }
        },

        async generatePlan() {
            if (this.generatingPlan) {
                return;
            }

            this.generatingPlan = true;

            try {
                const response = await fetch(this.generatePlanUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        patient_id: this.patientId,
                        assessment_id: this.assessmentId,
                    }),
                });

                const data = await response.json();

                if (data.csrf_hash) {
                    this.csrfToken = data.csrf_hash;
                }

                if (! data.success) {
                    alert(data.message || 'Erro ao gerar o plano terapêutico.');
                    return;
                }

                window.location.assign(data.redirect);
            } catch (error) {
                alert('Erro ao gerar o plano terapêutico.');
            } finally {
                this.generatingPlan = false;
            }
        },

        async exportPdf() {
            if (this.exporting) {
                return;
            }

            this.exporting = true;

            try {
                const response = await fetch(this.exportPdfUrl, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                const contentType = response.headers.get('Content-Type') || '';

                if (! response.ok || contentType.indexOf('application/pdf') === -1) {
                    alert('Erro ao gerar o PDF. Verifique se a avaliação está finalizada.');
                    return;
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);

                const link = document.createElement('a');
                link.href = url;
                link.download = `avaliacao_${this.assessmentId}.pdf`;
                document.body.appendChild(link);
                link.click();
                link.remove();

                window.URL.revokeObjectURL(url);
            } catch (error) {
                alert('Erro ao gerar o PDF.');
            } finally {
                this.exporting = false;
            }
        },
    };
}
</script>

<?= $this->endSection() ?>

