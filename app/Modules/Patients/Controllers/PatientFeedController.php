<?php

namespace App\Modules\Patients\Controllers;

use App\Controllers\BaseController;
use App\Modules\Patients\Models\PatientMilestoneModel;
use App\Modules\Patients\Models\PatientModel;
use App\Modules\Patients\Models\PatientPostModel;
use CodeIgniter\HTTP\ResponseInterface;

class PatientFeedController extends BaseController
{
    protected PatientPostModel $postModel;

    protected PatientMilestoneModel $milestoneModel;

    protected PatientModel $patientModel;

    public function __construct()
    {
        $this->postModel      = new PatientPostModel();
        $this->milestoneModel = new PatientMilestoneModel();
        $this->patientModel   = new PatientModel();
    }

    /**
     * Cria uma publicacao no mural do paciente (AJAX).
     */
    public function storePost(): ResponseInterface
    {
        $payload = $this->requestPayload();

        $patient = $this->patientForId((int) ($payload['patient_id'] ?? 0));
        if ($patient === null) {
            return $this->jsonError(404, 'Paciente não encontrado.');
        }

        $content = trim((string) ($payload['content'] ?? ''));
        $type    = (string) ($payload['type'] ?? '');

        if ($content === '') {
            return $this->jsonError(422, 'O conteúdo da publicação é obrigatório.');
        }

        if (! array_key_exists($type, PatientPostModel::POST_TYPES)) {
            return $this->jsonError(422, 'Tipo de publicação inválido.');
        }

        $data = [
            'tenant_id'  => $this->currentTenantId(),
            'patient_id' => (int) $patient['id'],
            'user_id'    => (int) session('user_id'),
            'content'    => $content,
            'type'       => $type,
        ];

        if (! $this->validateData($data, $this->postModel->getValidationRules(), $this->postModel->getValidationMessages())) {
            return $this->jsonError(422, 'Dados inválidos.', $this->validator->getErrors());
        }

        $postId = $this->postModel->insert($data);
        if ($postId === false) {
            return $this->jsonError(500, 'Falha ao publicar no mural.', $this->postModel->errors());
        }

        return $this->response->setJSON([
            'success'   => true,
            'message'   => 'Publicação criada com sucesso.',
            'post'      => $this->postModel->findWithAuthor((int) $postId),
            'csrf_hash' => csrf_hash(),
        ]);
    }

    /**
     * Cria um marco na jornada do paciente (AJAX).
     */
    public function storeMilestone(): ResponseInterface
    {
        $payload = $this->requestPayload();

        $patient = $this->patientForId((int) ($payload['patient_id'] ?? 0));
        if ($patient === null) {
            return $this->jsonError(404, 'Paciente não encontrado.');
        }

        $title       = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $eventDate   = (string) ($payload['event_date'] ?? '');
        $icon        = trim((string) ($payload['icon'] ?? ''));

        if ($title === '') {
            return $this->jsonError(422, 'O título do marco é obrigatório.');
        }

        if ($eventDate === '') {
            return $this->jsonError(422, 'A data do marco é obrigatória.');
        }

        $data = [
            'tenant_id'   => $this->currentTenantId(),
            'patient_id'  => (int) $patient['id'],
            'title'       => $title,
            'description' => $description !== '' ? $description : null,
            'event_date'  => $eventDate,
            'icon'        => $icon !== '' ? $icon : null,
        ];

        if (! $this->validateData($data, $this->milestoneModel->getValidationRules(), $this->milestoneModel->getValidationMessages())) {
            return $this->jsonError(422, 'Dados inválidos.', $this->validator->getErrors());
        }

        $milestoneId = $this->milestoneModel->insert($data);
        if ($milestoneId === false) {
            return $this->jsonError(500, 'Falha ao salvar o marco.', $this->milestoneModel->errors());
        }

        return $this->response->setJSON([
            'success'   => true,
            'message'   => 'Marco adicionado com sucesso.',
            'milestone' => $this->milestoneModel->findForPatient((int) $milestoneId),
            'csrf_hash' => csrf_hash(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function patientForId(int $patientId): ?array
    {
        return $patientId > 0 ? $this->patientModel->find($patientId) : null;
    }

    private function currentTenantId(): ?int
    {
        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
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
}
