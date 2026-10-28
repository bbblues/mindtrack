<?php

namespace App\Modules\Plans\Controllers;

use App\Controllers\BaseController;
use App\Modules\Assessments\Models\PatientAssessmentItemModel;
use App\Modules\Assessments\Models\PatientAssessmentModel;
use App\Modules\Patients\Models\PatientModel;
use App\Modules\Plans\Models\PlanItemModel;
use App\Modules\Plans\Models\TherapeuticPlanModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class TherapeuticPlanController extends BaseController
{
    protected TherapeuticPlanModel $planModel;

    protected PlanItemModel $planItemModel;

    protected PatientModel $patientModel;

    protected PatientAssessmentModel $assessmentModel;

    protected PatientAssessmentItemModel $assessmentItemModel;

    public function __construct()
    {
        $this->planModel           = new TherapeuticPlanModel();
        $this->planItemModel       = new PlanItemModel();
        $this->patientModel        = new PatientModel();
        $this->assessmentModel     = new PatientAssessmentModel();
        $this->assessmentItemModel = new PatientAssessmentItemModel();
    }

    /**
     * Exibe o plano terapeutico ativo do paciente.
     */
    public function show($patientId = null): string|RedirectResponse
    {
        $patient = $this->patientModel->find((int) $patientId);

        if ($patient === null) {
            return redirect()->to(site_url('patients'))->with('error', 'Paciente nao encontrado.');
        }

        $plan = $this->planModel
            ->where('patient_id', (int) $patient['id'])
            ->where('status', 'active')
            ->orderBy('id', 'DESC')
            ->first();

        $items = $plan !== null ? $this->planItems((int) $plan['id']) : [];

        return view('App\Modules\Plans\Views\show', [
            'title'   => 'Plano Terapeutico',
            'patient' => $patient,
            'plan'    => $plan,
            'items'   => $items,
        ]);
    }

    /**
     * Cria (ou reutiliza) o plano do paciente e importa em massa os itens
     * "Errados/Nao Avaliados" da avaliacao informada.
     */
    public function store(): ResponseInterface|RedirectResponse
    {
        $isAjax       = $this->request->isAJAX();
        $payload      = $this->requestPayload();
        $patientId    = (int) ($payload['patient_id'] ?? 0);
        $assessmentId = (int) ($payload['assessment_id'] ?? 0);

        if ($patientId <= 0) {
            return $this->respond($isAjax, 422, 'Paciente invalido.');
        }

        $patient = $this->patientModel->find($patientId);

        if ($patient === null) {
            return $this->respond($isAjax, 404, 'Paciente nao encontrado.');
        }

        $tenantId = is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;

        $plan = $this->planModel
            ->where('patient_id', $patientId)
            ->where('status', 'active')
            ->orderBy('id', 'DESC')
            ->first();

        if ($plan === null) {
            $planId = $this->planModel->insert([
                'tenant_id'  => $tenantId,
                'patient_id' => $patientId,
                'status'     => 'active',
                'start_date' => date('Y-m-d'),
            ]);

            if ($planId === false) {
                return $this->respond($isAjax, 500, 'Falha ao criar o plano terapeutico.', $this->planModel->errors());
            }

            $planId = (int) $planId;
        } else {
            $planId = (int) $plan['id'];
        }

        $imported = 0;

        if ($assessmentId > 0) {
            $imported = $this->importFromAssessment($assessmentId, $planId, $tenantId);
        }

        $redirect = site_url('plans/show/' . $patientId);

        if ($isAjax) {
            return $this->response->setJSON([
                'success'   => true,
                'message'   => $imported > 0 ? $imported . ' meta(s) importada(s) para o plano.' : 'Plano terapeutico pronto.',
                'redirect'  => $redirect,
                'csrf_hash' => csrf_hash(),
            ]);
        }

        return redirect()->to($redirect)->with('success', 'Plano terapeutico gerado com sucesso.');
    }

    /**
     * Alterna o status de uma meta entre "Em Andamento" e "Adquirido".
     */
    public function toggleItem($id = null): ResponseInterface
    {
        $item = $this->planItemModel->find((int) $id);

        if ($item === null) {
            return $this->jsonError(404, 'Meta nao encontrada.');
        }

        $newStatus = ($item['status'] ?? '') === 'acquired' ? 'in_progress' : 'acquired';

        if (! $this->planItemModel->update((int) $item['id'], ['status' => $newStatus])) {
            return $this->jsonError(500, 'Falha ao atualizar a meta.', $this->planItemModel->errors());
        }

        return $this->response->setJSON([
            'success'   => true,
            'status'    => $newStatus,
            'csrf_hash' => csrf_hash(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function planItems(int $planId): array
    {
        return $this->planItemModel
            ->select('plan_items.*, protocol_items.name AS item_name, protocol_items.description AS item_description, protocol_skills.name AS skill_name, protocol_levels.name AS level_name')
            ->join('protocol_items', 'protocol_items.id = plan_items.protocol_item_id', 'left')
            ->join('protocol_skills', 'protocol_skills.id = protocol_items.skill_id', 'left')
            ->join('protocol_levels', 'protocol_levels.id = protocol_skills.level_id', 'left')
            ->where('plan_items.therapeutic_plan_id', $planId)
            ->orderBy('plan_items.sort_order', 'ASC')
            ->orderBy('plan_items.id', 'ASC')
            ->findAll();
    }

    /**
     * Importa para o plano os itens da avaliacao cujo valor seja <= 0,
     * ou seja, os itens marcados como "Errado" ou ainda "Nao Avaliados".
     */
    private function importFromAssessment(int $assessmentId, int $planId, ?int $tenantId): int
    {
        $assessment = $this->assessmentModel->find($assessmentId);

        if ($assessment === null) {
            return 0;
        }

        $rows = $this->assessmentItemModel
            ->select('patient_assessment_items.protocol_item_id, protocol_items.name, protocol_items.description')
            ->join('protocol_items', 'protocol_items.id = patient_assessment_items.protocol_item_id', 'inner')
            ->where('patient_assessment_items.assessment_id', $assessmentId)
            ->where('patient_assessment_items.value_at_time <=', 0)
            ->orderBy('patient_assessment_items.id', 'ASC')
            ->findAll();

        $existingRows = $this->planItemModel
            ->where('therapeutic_plan_id', $planId)
            ->findAll();

        $existingIds = [];
        $maxSort     = 0;

        foreach ($existingRows as $existing) {
            $maxSort = max($maxSort, (int) ($existing['sort_order'] ?? 0));

            if ($existing['protocol_item_id'] !== null) {
                $existingIds[] = (int) $existing['protocol_item_id'];
            }
        }

        $imported = 0;

        foreach ($rows as $row) {
            $protocolItemId = (int) $row['protocol_item_id'];

            if (in_array($protocolItemId, $existingIds, true)) {
                continue;
            }

            $maxSort++;

            $result = $this->planItemModel->insert([
                'tenant_id'           => $tenantId,
                'therapeutic_plan_id' => $planId,
                'protocol_item_id'    => $protocolItemId,
                'objective'           => $this->buildObjective($row),
                'status'              => 'in_progress',
                'sort_order'          => $maxSort,
            ]);

            if ($result !== false) {
                $imported++;
                $existingIds[] = $protocolItemId;
            }
        }

        return $imported;
    }

    private function buildObjective(array $row): string
    {
        $parts = array_filter([
            trim((string) ($row['name'] ?? '')),
            trim((string) ($row['description'] ?? '')),
        ], static fn (string $value): bool => $value !== '');

        return $parts !== [] ? implode(' — ', $parts) : 'Meta importada da avaliacao';
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(): array
    {
        try {
            $json = $this->request->getJSON(true);
        } catch (\Throwable) {
            $json = null;
        }

        if (is_array($json) && $json !== []) {
            return $json;
        }

        $post = $this->request->getPost();

        return is_array($post) ? $post : [];
    }

    private function jsonError(int $status, string $message, array $errors = []): ResponseInterface
    {
        $body = [
            'success'   => false,
            'message'   => $message,
            'csrf_hash' => csrf_hash(),
        ];

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return $this->response->setStatusCode($status)->setJSON($body);
    }

    private function respond(bool $isAjax, int $status, string $message, array $errors = []): ResponseInterface|RedirectResponse
    {
        if ($isAjax) {
            return $this->jsonError($status, $message, $errors);
        }

        return redirect()->back()->with('error', $message);
    }
}
