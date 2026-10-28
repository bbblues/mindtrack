<?php

namespace App\Controllers;

use App\Modules\Core\Models\ModuleModel;
use App\Modules\Core\Models\TenantModel;
use App\Modules\Patients\Models\PatientModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\ResponseInterface;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $isSuperAdmin = (int) session('is_super_admin') === 1;
        $roleSlugs    = (array) session('user_role_slugs');

        $user = [
            'name'           => (string) (session('user_name') ?? 'Usuario'),
            'is_super_admin' => $isSuperAdmin,
        ];

        // Cargos Admin e Gestor enxergam o painel de indicadores da clinica.
        if (array_intersect(['admin', 'gestor'], $roleSlugs) !== []) {
            return view('dashboard_management', [
                'title'        => 'Dashboard de Gestão',
                'isSuperAdmin' => $isSuperAdmin,
                'user'         => $user,
            ]);
        }

        $metrics = [];
        if ($isSuperAdmin) {
            $tenantModel  = new TenantModel();
            $patientModel = new PatientModel();
            $moduleModel  = new ModuleModel();

            $metrics = [
                'total_tenants'  => (int) $tenantModel->countAllResults(),
                'total_patients' => (int) $patientModel->countAllResults(),
                'total_modules'  => (int) $moduleModel->countAllResults(),
            ];
        }

        return view('dashboard', [
            'title'        => 'Dashboard',
            'isSuperAdmin' => $isSuperAdmin,
            'metrics'      => $metrics,
            'user'         => $user,
        ]);
    }

    /**
     * Retorna os indicadores do dashboard de gestao em JSON.
     *
     * Aceita o parametro opcional ?month=YYYY-MM (padrao: mes corrente).
     */
    public function getData(): ResponseInterface
    {
        $tenantId = $this->tenantId();
        $month    = $this->resolveMonth();

        [$firstDay, $lastDay] = $this->monthBounds($month);

        $totalPatients     = $this->countActivePatients($tenantId);
        $appointmentsMonth = $this->countAppointments($tenantId, $firstDay, $lastDay, []);
        $realizados        = $this->countAppointments($tenantId, $firstDay, $lastDay, ['realizado']);
        $faltas            = $this->countAppointments($tenantId, $firstDay, $lastDay, ['falta_paciente', 'falta_profissional']);

        $concluidos = $realizados + $faltas;
        $taxaFaltas = $concluidos > 0 ? round(($faltas / $concluidos) * 100, 1) : 0.0;

        return $this->response->setJSON([
            'success'           => true,
            'kpi'               => [
                'total_patients'     => $totalPatients,
                'appointments_month' => $appointmentsMonth,
                'realizados_month'   => $realizados,
                'faltas_month'       => $faltas,
                'taxa_faltas'        => $taxaFaltas,
                'taxa_assiduidade'   => round(100 - $taxaFaltas, 1),
            ],
            'monthly'           => $this->monthlySeries($tenantId, $month),
            'top_professionals' => $this->topProfessionals($tenantId, $firstDay, $lastDay),
        ]);
    }

    /**
     * @return array{0: string, 1: string} [primeiro dia, ultimo dia]
     */
    private function monthBounds(string $month): array
    {
        return [
            $month . '-01 00:00:00',
            date('Y-m-t 23:59:59', strtotime($month . '-01')),
        ];
    }

    /**
     * Serie mensal dos ultimos 6 meses (realizados x faltas).
     *
     * @return array{labels: list<string>, realizados: list<int>, faltas: list<int>}
     */
    private function monthlySeries(?int $tenantId, string $month): array
    {
        $labels     = [];
        $realizados = [];
        $faltas     = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $m = date('Y-m', strtotime(sprintf('%s-01 -%d months', $month, $offset)));
            [$firstDay, $lastDay] = $this->monthBounds($m);

            $labels[]     = $this->monthLabel($m);
            $realizados[] = $this->countAppointments($tenantId, $firstDay, $lastDay, ['realizado']);
            $faltas[]     = $this->countAppointments($tenantId, $firstDay, $lastDay, ['falta_paciente', 'falta_profissional']);
        }

        return [
            'labels'     => $labels,
            'realizados' => $realizados,
            'faltas'     => $faltas,
        ];
    }

    /**
     * Top 5 profissionais com mais atendimentos realizados no periodo.
     *
     * @return list<array{name: string, realizados: int}>
     */
    private function topProfessionals(?int $tenantId, string $firstDay, string $lastDay): array
    {
        $rows = $this->appointmentsBuilder($tenantId)
            ->select('users.name AS name, COUNT(appointments.id) AS realizados', false)
            ->join('users', 'users.id = appointments.professional_id', 'left')
            ->where('users.deleted_at', null)
            ->where('appointments.status', 'realizado')
            ->where('appointments.start_time >=', $firstDay)
            ->where('appointments.start_time <=', $lastDay)
            ->groupBy('appointments.professional_id, users.name')
            ->orderBy('realizados', 'DESC', false)
            ->limit(5)
            ->get()
            ->getResultArray();

        return array_map(static function (array $row): array {
            return [
                'name'       => (string) ($row['name'] ?? 'Sem nome'),
                'realizados' => (int) ($row['realizados'] ?? 0),
            ];
        }, $rows);
    }

    /**
     * Builder base da tabela de agendamentos ja com tenant e soft delete.
     */
    private function appointmentsBuilder(?int $tenantId): BaseBuilder
    {
        $builder = db_connect()->table('appointments');

        if ($tenantId !== null) {
            $builder->where('appointments.tenant_id', $tenantId);
        }

        return $builder->where('appointments.deleted_at', null);
    }

    private function countActivePatients(?int $tenantId): int
    {
        $builder = db_connect()->table('patients')
            ->where('patients.deleted_at', null)
            ->where('patients.is_active', 1);

        if ($tenantId !== null) {
            $builder->where('patients.tenant_id', $tenantId);
        }

        return (int) $builder->countAllResults();
    }

    private function countAppointments(?int $tenantId, string $firstDay, string $lastDay, array $statuses): int
    {
        $builder = $this->appointmentsBuilder($tenantId)
            ->where('appointments.start_time >=', $firstDay)
            ->where('appointments.start_time <=', $lastDay);

        if ($statuses !== []) {
            $builder->whereIn('appointments.status', $statuses);
        }

        return (int) $builder->countAllResults();
    }

    private function tenantId(): ?int
    {
        $tenantId = session('tenant_id');

        return is_numeric($tenantId) ? (int) $tenantId : null;
    }

    private function resolveMonth(): string
    {
        $month = (string) $this->request->getGet('month');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1 ? $month : date('Y-m');
    }

    private function monthLabel(string $month): string
    {
        $names = [
            '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
            '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
            '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez',
        ];

        [$year, $num] = explode('-', $month);

        return ($names[$num] ?? '') . '/' . substr($year, -2);
    }
}
