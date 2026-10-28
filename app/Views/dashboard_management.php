<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Dashboard de Gestão</h1>
        <p class="mt-1 text-sm text-gray-500">Indicadores operacionais da clínica.</p>
    </div>

    <?php if (session('success')): ?>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= esc(session('success')) ?></div>
    <?php endif; ?>

    <?php if (session('error')): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <!-- Cards de KPI -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Total Pacientes</h2>
            <p id="kpi-pacientes" class="mt-2 text-3xl font-bold text-gray-800">0</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Agendamentos no Mês</h2>
            <p id="kpi-agendamentos" class="mt-2 text-3xl font-bold text-gray-800">0</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Taxa de Faltas</h2>
            <p id="kpi-faltas" class="mt-2 text-3xl font-bold text-red-600">0%</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Taxa de Assiduidade</h2>
            <p id="kpi-assiduidade" class="mt-2 text-3xl font-bold text-green-600">0%</p>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Realizados x Faltas (últimos 6 meses)</h2>
            <div class="relative mt-4 h-80">
                <canvas id="chart-realizados-faltas"></canvas>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Atendimentos por Profissional</h2>
            <div class="relative mt-4 h-80">
                <canvas id="chart-profissionais"></canvas>
            </div>
        </div>
    </div>

    <!-- Ranking -->
    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Top 5 Profissionais (Realizados)</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Profissional</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Atendimentos</th>
                    </tr>
                </thead>
                <tbody id="ranking-profissionais" class="divide-y divide-gray-200"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    var endpoint = <?= json_encode(site_url('dashboard/data')) ?>;

    var barChart = null;
    var doughnutChart = null;
    var colors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444'];

    async function loadDashboard() {
        try {
            var response = await fetch(endpoint, {
                headers: { Accept: 'application/json' }
            });

            if (!response.ok) {
                throw new Error('Falha ao carregar indicadores (HTTP ' + response.status + ').');
            }

            var payload = await response.json();

            renderKpis(payload.kpi || {});
            renderCharts(payload.monthly || {}, payload.top_professionals || []);
            renderRanking(payload.top_professionals || []);
        } catch (error) {
            console.error('[Dashboard] ', error);
        }
    }

    function renderKpis(kpi) {
        setText('kpi-pacientes', kpi.total_patients || 0);
        setText('kpi-agendamentos', kpi.appointments_month || 0);
        setText('kpi-faltas', (kpi.taxa_faltas || 0) + '%');
        setText('kpi-assiduidade', (kpi.taxa_assiduidade || 0) + '%');
    }

    function renderCharts(monthly, top) {
        var barCanvas = document.getElementById('chart-realizados-faltas');
        if (barCanvas) {
            if (barChart) {
                barChart.destroy();
            }

            barChart = new Chart(barCanvas, {
                type: 'bar',
                data: {
                    labels: monthly.labels || [],
                    datasets: [
                        {
                            label: 'Realizados',
                            data: monthly.realizados || [],
                            backgroundColor: '#10b981'
                        },
                        {
                            label: 'Faltas',
                            data: monthly.faltas || [],
                            backgroundColor: '#ef4444'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }

        var doughnutCanvas = document.getElementById('chart-profissionais');
        if (doughnutCanvas) {
            if (doughnutChart) {
                doughnutChart.destroy();
            }

            doughnutChart = new Chart(doughnutCanvas, {
                type: 'doughnut',
                data: {
                    labels: top.map(function (item) { return item.name; }),
                    datasets: [
                        {
                            data: top.map(function (item) { return item.realizados; }),
                            backgroundColor: colors
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    }

    function renderRanking(top) {
        var tbody = document.getElementById('ranking-profissionais');
        if (!tbody) {
            return;
        }

        if (!top.length) {
            tbody.innerHTML = '<tr><td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">Nenhum atendimento realizado no período.</td></tr>';
            return;
        }

        tbody.innerHTML = top.map(function (item, index) {
            return '<tr>' +
                '<td class="px-4 py-3 text-sm font-semibold text-gray-500">' + (index + 1) + '</td>' +
                '<td class="px-4 py-3 text-sm font-medium text-gray-800">' + escapeHtml(item.name) + '</td>' +
                '<td class="px-4 py-3 text-right text-sm font-semibold text-gray-800">' + (item.realizados || 0) + '</td>' +
                '</tr>';
        }).join('');
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    }

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    loadDashboard();
})();
</script>

<?= $this->endSection() ?>