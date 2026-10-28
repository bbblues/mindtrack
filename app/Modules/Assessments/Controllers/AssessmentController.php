<?php

namespace App\Modules\Assessments\Controllers;

use App\Controllers\BaseController;
use App\Modules\Assessments\Models\PatientAssessmentItemModel;
use App\Modules\Assessments\Models\PatientAssessmentModel;
use App\Modules\Patients\Models\PatientModel;
use App\Modules\Protocols\Models\ProtocolItemModel;
use App\Modules\Protocols\Models\ProtocolLevelModel;
use App\Modules\Protocols\Models\ProtocolModel;
use App\Modules\Protocols\Models\ProtocolSkillModel;
use App\Modules\Protocols\Models\ProtocolStatusModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class AssessmentController extends BaseController
{
    protected PatientAssessmentModel $assessmentModel;

    protected PatientAssessmentItemModel $assessmentItemModel;

    protected PatientModel $patientModel;

    protected ProtocolModel $protocolModel;

    protected ProtocolLevelModel $levelModel;

    protected ProtocolSkillModel $skillModel;

    protected ProtocolItemModel $itemModel;

    protected ProtocolStatusModel $statusModel;

    public function __construct()
    {
        $this->assessmentModel     = new PatientAssessmentModel();
        $this->assessmentItemModel = new PatientAssessmentItemModel();
        $this->patientModel        = new PatientModel();
        $this->protocolModel       = new ProtocolModel();
        $this->levelModel          = new ProtocolLevelModel();
        $this->skillModel          = new ProtocolSkillModel();
        $this->itemModel           = new ProtocolItemModel();
        $this->statusModel         = new ProtocolStatusModel();
    }

    /**
     * Inicia uma avaliacao para um paciente com um protocolo.
     *
     * Cria a patient_assessments e popula os patient_assessment_items
     * com os itens do protocolo (status_id = null, value_at_time = 0).
     */
    public function create(): RedirectResponse
    {
        $patientId  = (int) $this->request->getPost('patient_id');
        $protocolId = (int) $this->request->getPost('protocol_id');

        if ($patientId <= 0 || $protocolId <= 0) {
            return redirect()->back()->with('error', 'Paciente e protocolo sao obrigatorios.');
        }

        $patient  = $this->patientModel->find($patientId);
        $protocol = $this->protocolModel->find($protocolId);

        if ($patient === null || $protocol === null) {
            return redirect()->back()->with('error', 'Paciente ou protocolo nao encontrado.');
        }

        $tenantId = is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;

        $assessmentId = $this->assessmentModel->insert([
            'tenant_id'   => $tenantId,
            'patient_id'  => $patientId,
            'protocol_id' => $protocolId,
            'score'       => 0,
            'status'      => 'in_progress',
        ]);

        if ($assessmentId === false) {
            return redirect()->back()->with('error', 'Falha ao iniciar a avaliacao.');
        }

        $assessmentId = (int) $assessmentId;

        foreach ($this->protocolItemsByProtocol($protocolId) as $item) {
            $this->assessmentItemModel->insert([
                'tenant_id'        => $tenantId,
                'assessment_id'    => $assessmentId,
                'protocol_item_id' => (int) $item['id'],
                'status_id'        => null,
                'value_at_time'    => 0,
            ]);
        }

        return redirect()->to(site_url('assessments/show/' . $assessmentId))
            ->with('success', 'Avaliacao iniciada com sucesso.');
    }

    /**
     * Exibe a interface de execucao da avaliacao.
     */
    public function show($id = null): string|RedirectResponse
    {
        $assessment = $this->assessmentModel->find((int) $id);

        if ($assessment === null) {
            return redirect()->to(site_url('patients'))->with('error', 'Avaliacao nao encontrada.');
        }

        $patient  = $this->patientModel->find((int) $assessment['patient_id']);
        $protocol = $this->protocolModel->find((int) $assessment['protocol_id']);

        if ($patient === null || $protocol === null) {
            return redirect()->to(site_url('patients'))->with('error', 'Paciente ou protocolo nao encontrado.');
        }

        $statuses = $this->statusModel
            ->where('protocol_id', (int) $protocol['id'])
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        return view('App\Modules\Assessments\Views\show', [
            'title'      => 'Avaliacao',
            'assessment' => $assessment,
            'patient'    => $patient,
            'protocol'   => $protocol,
            'statuses'   => $statuses,
            'levels'     => $this->buildLevels((int) $protocol['id'], (int) $assessment['id']),
        ]);
    }

    /**
     * Salva o status de um item via AJAX, recalcula o score total e
     * devolve o novo score + o novo csrf_hash.
     */
    public function updateItem(): ResponseInterface
    {
        $payload = $this->requestPayload();

        $assessmentItemId = (int) ($payload['assessment_item_id'] ?? 0);

        if ($assessmentItemId <= 0) {
            return $this->jsonError(422, 'Item de avaliacao invalido.');
        }

        $assessmentItem = $this->assessmentItemModel->find($assessmentItemId);

        if ($assessmentItem === null) {
            return $this->jsonError(404, 'Item de avaliacao nao encontrado.');
        }

        $assessment = $this->assessmentModel->find((int) $assessmentItem['assessment_id']);

        if ($assessment === null) {
            return $this->jsonError(404, 'Avaliacao nao encontrada.');
        }

        $statusId = null;
        $raw      = $payload['status_id'] ?? null;

        if ($raw !== null && $raw !== '') {
            $statusId = (int) $raw;
        }

        $value = 0;

        if ($statusId !== null) {
            $status = $this->statusModel->find($statusId);

            if ($status === null || (int) $status['protocol_id'] !== (int) $assessment['protocol_id']) {
                return $this->jsonError(422, 'Status invalido para esta avaliacao.');
            }

            $value = (int) ($status['value'] ?? 0);
        }

        if (! $this->assessmentItemModel->update($assessmentItemId, [
            'status_id'     => $statusId,
            'value_at_time' => $value,
        ])) {
            return $this->jsonError(500, 'Falha ao salvar o item.', $this->assessmentItemModel->errors());
        }

        $score = $this->recalculateScore((int) $assessment['id']);
        $this->assessmentModel->update((int) $assessment['id'], ['score' => $score]);

        return $this->response->setJSON([
            'success'   => true,
            'message'   => 'Item salvo.',
            'score'     => $score,
            'csrf_hash' => csrf_hash(),
        ]);
    }

    /**
     * Monta a estrutura aninhada niveis -> habilidades -> itens, ja com a
     * resposta atual de cada item (assessment_item_id, status_id e valor).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildLevels(int $protocolId, int $assessmentId): array
    {
        $levels = $this->levelModel
            ->where('protocol_id', $protocolId)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $assessmentItems = $this->assessmentItemModel
            ->where('assessment_id', $assessmentId)
            ->findAll();

        $answersByProtocolItem = [];

        foreach ($assessmentItems as $answer) {
            $answersByProtocolItem[(int) $answer['protocol_item_id']] = $answer;
        }

        $result = [];

        foreach ($levels as $level) {
            $levelId = (int) $level['id'];
            $skills  = $this->skillModel
                ->where('level_id', $levelId)
                ->orderBy('sort_order', 'ASC')
                ->findAll();

            $skillRows = [];

            foreach ($skills as $skill) {
                $skillId       = (int) $skill['id'];
                $protocolItems = $this->itemModel
                    ->where('skill_id', $skillId)
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();

                $itemRows = [];

                foreach ($protocolItems as $protocolItem) {
                    $answer = $answersByProtocolItem[(int) $protocolItem['id']] ?? null;

                    $itemRows[] = [
                        'protocol_item_id'   => (int) $protocolItem['id'],
                        'name'               => (string) $protocolItem['name'],
                        'description'        => (string) ($protocolItem['description'] ?? ''),
                        'assessment_item_id' => $answer !== null ? (int) $answer['id'] : null,
                        'status_id'          => $answer !== null ? $this->nullableId($answer['status_id'] ?? null) : null,
                        'value_at_time'      => $answer !== null ? (int) ($answer['value_at_time'] ?? 0) : 0,
                    ];
                }

                $skillRows[] = [
                    'id'          => $skillId,
                    'name'        => (string) $skill['name'],
                    'description' => (string) ($skill['description'] ?? ''),
                    'items'       => $itemRows,
                ];
            }

            $result[] = [
                'id'          => $levelId,
                'name'        => (string) $level['name'],
                'description' => (string) ($level['description'] ?? ''),
                'skills'      => $skillRows,
            ];
        }

        return $result;
    }

    /**
     * Lista todos os protocol_items de um protocolo (atravessando levels -> skills).
     *
     * @return array<int, array<string, mixed>>
     */
    private function protocolItemsByProtocol(int $protocolId): array
    {
        return $this->itemModel
            ->select('protocol_items.*, protocol_levels.protocol_id')
            ->join('protocol_skills', 'protocol_skills.id = protocol_items.skill_id', 'inner')
            ->join('protocol_levels', 'protocol_levels.id = protocol_skills.level_id', 'inner')
            ->where('protocol_levels.protocol_id', $protocolId)
            ->orderBy('protocol_levels.sort_order', 'ASC')
            ->orderBy('protocol_skills.sort_order', 'ASC')
            ->orderBy('protocol_items.sort_order', 'ASC')
            ->findAll();
    }

    private function recalculateScore(int $assessmentId): int
    {
        $items = $this->assessmentItemModel
            ->where('assessment_id', $assessmentId)
            ->findAll();

        $score = 0;

        foreach ($items as $item) {
            $score += (int) ($item['value_at_time'] ?? 0);
        }

        return $score;
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

    private function nullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}


