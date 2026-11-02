<?php

namespace App\Modules\Assessments\Controllers;

use App\Controllers\BaseController;
use App\Modules\Assessments\Models\PatientAssessmentItemModel;
use App\Modules\Assessments\Models\PatientAssessmentModel;
use App\Modules\Assessments\Services\RadarChartService;
use App\Modules\Assessments\Services\ScoreCalculatorService;
use App\Modules\Core\Models\TenantModel;
use App\Modules\Patients\Models\PatientModel;
use App\Modules\Protocols\Models\ProtocolItemModel;
use App\Modules\Protocols\Models\ProtocolLevelModel;
use App\Modules\Protocols\Models\ProtocolModel;
use App\Modules\Protocols\Models\ProtocolSkillModel;
use App\Modules\Protocols\Models\ProtocolStatusModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

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

    protected ScoreCalculatorService $scoreCalculator;

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
        $this->scoreCalculator     = new ScoreCalculatorService();
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
                'score_achieved'   => 0.0,
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

        $view = match ($protocol['view_slug'] ?? null) {
            'vb-mapp' => 'App\Modules\Assessments\Views\protocols\vb_mapp',
            'afls'    => 'App\Modules\Assessments\Views\protocols\afls',
            default   => 'App\Modules\Assessments\Views\show',
        };

        return view($view, [
            'title'      => 'Avaliacao',
            'assessment' => $assessment,
            'patient'    => $patient,
            'protocol'   => $protocol,
            'statuses'   => $statuses,
            'levels'     => $this->buildLevels((int) $protocol['id'], (int) $assessment['id']),
            'scores'     => $this->scoreCalculator->calculate((int) $assessment['id']),
        ]);
    }

    /**
     * Gera o PDF da avaliacao finalizada (via Dompdf) e envia como download.
     */
    public function exportPdf($id = null): ResponseInterface|RedirectResponse
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        try {
            $assessment = $this->assessmentModel->find((int) $id);

            if ($assessment === null) {
                return redirect()->to(site_url('patients'))->with('error', 'Avaliacao nao encontrada.');
            }

            if (($assessment['status'] ?? '') !== 'completed') {
                return redirect()->to(site_url('assessments/show/' . (int) $assessment['id']))
                    ->with('error', 'Finalize a avaliacao antes de exportar o PDF.');
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

            $statusNames = [];

            foreach ($statuses as $status) {
                $statusNames[(int) $status['id']] = (string) $status['name'];
            }

            $clinic   = null;
            $tenantId = is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;

            if ($tenantId !== null) {
                $clinic = (new TenantModel())->find($tenantId);
            }

            $levels = $this->buildLevels((int) $protocol['id'], (int) $assessment['id']);
            $scores = $this->scoreCalculator->calculate((int) $assessment['id']);

            $sectionSummaries = $this->sectionSummaries($levels, $scores);
            $radarImage       = (new RadarChartService())->render($sectionSummaries);

            $view = match ($protocol['view_slug'] ?? null) {
                'vb-mapp' => 'App\Modules\Assessments\Views\pdf\pdf_vb_mapp',
                'afls'    => 'App\Modules\Assessments\Views\pdf\pdf_afls',
                default   => 'App\Modules\Assessments\Views\pdf_assessment',
            };

            $html = view($view, [
                'assessment'       => $assessment,
                'patient'          => $patient,
                'protocol'         => $protocol,
                'statusNames'      => $statusNames,
                'levels'           => $levels,
                'scores'           => $scores,
                'sectionSummaries' => $sectionSummaries,
                'radarImage'       => $radarImage,
                'clinic'           => $clinic,
                'professional'     => session('user_name'),
            ]);

            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $pdf      = $dompdf->output();
            $filename = 'avaliacao_' . (int) $assessment['id'] . '_' . date('Ymd_His') . '.pdf';

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setHeader('Content-Length', (string) strlen($pdf))
                ->setBody($pdf);
        } catch (\Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Erro ao gerar PDF: ' . $e->getMessage(),
            ]);
        }
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

        if (($assessment['status'] ?? '') === 'completed') {
            return $this->jsonError(409, 'Avaliacao finalizada: os itens nao podem ser alterados.');
        }

        $protocolItem = $this->itemModel->find((int) $assessmentItem['protocol_item_id']);
        $maxScore     = (float) ($protocolItem['max_score'] ?? 1.0);
        $step         = $this->resolveStep($protocolItem);

        $statusId    = null;
        $valueAtTime = 0;

        $rawStatus = $payload['status_id'] ?? null;

        if ($rawStatus !== null && $rawStatus !== '') {
            $statusId = (int) $rawStatus;
        }

        if ($statusId !== null) {
            $status = $this->statusModel->find($statusId);

            if ($status === null || (int) $status['protocol_id'] !== (int) $assessment['protocol_id']) {
                return $this->jsonError(422, 'Status invalido para esta avaliacao.');
            }

            $valueAtTime = (int) ($status['value'] ?? 0);
        }

        $rawScore = $payload['score_achieved'] ?? null;

        if ($rawScore !== null && $rawScore !== '') {
            if (! is_numeric($rawScore)) {
                return $this->jsonError(422, 'Nota invalida para este item.');
            }

            $scoreAchieved = (float) $rawScore;
        } else {
            $scoreAchieved = (float) $valueAtTime;
        }

        if ($scoreAchieved < 0 || $scoreAchieved > $maxScore) {
            return $this->jsonError(422, 'A nota deve estar entre 0 e ' . $maxScore . '.');
        }

        if (! $this->isScoreGranularityValid($scoreAchieved, $maxScore, $step)) {
            if ($maxScore <= 1.0) {
                return $this->jsonError(422, 'Este item aceita apenas 0 ou 1.');
            }

            return $this->jsonError(422, 'A nota deve ser um múltiplo de ' . $this->formatStep($step) . '.');
        }

        if (! $this->assessmentItemModel->update($assessmentItemId, [
            'status_id'      => $statusId,
            'value_at_time'  => $valueAtTime,
            'score_achieved' => $scoreAchieved,
        ])) {
            return $this->jsonError(500, 'Falha ao salvar o item.', $this->assessmentItemModel->errors());
        }

        $result = $this->scoreCalculator->calculate((int) $assessment['id']);
        $this->assessmentModel->update((int) $assessment['id'], ['score' => $result['total_score']]);

        return $this->response->setJSON([
            'success'        => true,
            'message'        => 'Item salvo.',
            'score'          => $result['total_score'],
            'total_possible' => $result['total_possible'],
            'percentage'     => $result['percentage'],
            'by_skill'       => $result['by_skill'],
            'by_section'     => $result['by_section'],
            'csrf_hash'      => csrf_hash(),
        ]);
    }

    /**
     * Finaliza a avaliacao, mudando o status para 'completed' e bloqueando
     * a edicao dos itens (o front passa a forcar o modo de visualizacao).
     */
    public function finalize($id = null): ResponseInterface
    {
        $assessment = $this->assessmentModel->find((int) $id);

        if ($assessment === null) {
            return $this->jsonError(404, 'Avaliacao nao encontrada.');
        }

        if (($assessment['status'] ?? '') === 'completed') {
            return $this->response->setJSON([
                'success'   => true,
                'message'   => 'Avaliacao ja finalizada.',
                'csrf_hash' => csrf_hash(),
            ]);
        }

        if (! $this->assessmentModel->update((int) $assessment['id'], ['status' => 'completed'])) {
            return $this->jsonError(500, 'Falha ao finalizar a avaliacao.', $this->assessmentModel->errors());
        }

        return $this->response->setJSON([
            'success'   => true,
            'message'   => 'Avaliacao finalizada.',
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
                        'max_score'          => (float) ($protocolItem['max_score'] ?? 1.0),
                        'sort_order'         => (int) ($protocolItem['sort_order'] ?? 0),
                        'metadata'           => $this->decodeMetadata($protocolItem['metadata'] ?? null),
                        'assessment_item_id' => $answer !== null ? (int) $answer['id'] : null,
                        'status_id'          => $answer !== null ? $this->nullableId($answer['status_id'] ?? null) : null,
                        'value_at_time'      => $answer !== null ? (int) ($answer['value_at_time'] ?? 0) : 0,
                        'score_achieved'     => $answer !== null ? (float) ($answer['score_achieved'] ?? 0) : 0.0,
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
                'section'     => (string) ($level['section'] ?? ''),
                'description' => (string) ($level['description'] ?? ''),
                'skills'      => $skillRows,
            ];
        }

        return $result;
    }

    /**
     * Monta o resumo por seção usado no resumo do PDF e no radar.
     *
     * @param list<array<string, mixed>> $levels
     * @param array<string, mixed>       $scores
     *
     * @return list<array{key: string, label: string, score: float, max: float, percentage: float}>
     */
    private function sectionSummaries(array $levels, array $scores): array
    {
        $bySection = [];

        foreach (($scores['by_section'] ?? []) as $row) {
            $bySection[(string) $row['section']] = $row;
        }

        $summaries = [];

        foreach ($levels as $level) {
            $key = $level['section'] !== '' ? $level['section'] : $level['name'];
            $agg = $bySection[$key] ?? ['score' => 0.0, 'max' => 0.0, 'percentage' => 0.0];

            $summaries[] = [
                'key'        => $key,
                'label'      => $this->humanizeSection($key),
                'score'      => (float) ($agg['score'] ?? 0),
                'max'        => (float) ($agg['max'] ?? 0),
                'percentage' => (float) ($agg['percentage'] ?? 0),
            ];
        }

        return $summaries;
    }

    private function humanizeSection(string $key): string
    {
        $label = trim($key);

        if ($label === '') {
            return 'Geral';
        }

        return mb_convert_case(mb_strtolower($label, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
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

    /**
     * Resolve o incremento (step) esperado para a nota de um item.
     *
     * Por padrao o incremento e 1 (notas inteiras). Itens com max_score <= 1
     * sao binarios e sempre usam step 1. Protocolos que precisarem de meio
     * ponto podem informar `step` no metadata do protocol_item.
     */
    private function resolveStep(?array $protocolItem): float
    {
        $maxScore = (float) ($protocolItem['max_score'] ?? 1.0);

        if ($maxScore <= 1.0) {
            return 1.0;
        }

        $metadata = $this->decodeMetadata($protocolItem['metadata'] ?? null);
        $step     = $metadata['step'] ?? null;

        if (is_numeric($step) && (float) $step > 0) {
            return (float) $step;
        }

        return 1.0;
    }

    /**
     * Valida a granularidade da nota conforme o incremento esperado.
     */
    private function isScoreGranularityValid(float $value, float $maxScore, float $step): bool
    {
        if ($maxScore <= 1.0) {
            return $value === 0.0 || $value === 1.0;
        }

        if ($step <= 0.0) {
            return true;
        }

        $remainder = fmod($value, $step);
        $epsilon   = 0.00001;

        return abs($remainder) < $epsilon || abs($remainder - $step) < $epsilon;
    }

    /**
     * Formata o step para exibicao em mensagens de erro.
     */
    private function formatStep(float $step): string
    {
        if ($step === (float) (int) $step) {
            return (string) (int) $step;
        }

        return rtrim(rtrim((string) round($step, 2), '0'), '.');
    }

    private function nullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeMetadata(mixed $value): ?array
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}


