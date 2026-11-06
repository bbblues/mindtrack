<?php

namespace App\Modules\Evolutions\Controllers;

use App\Controllers\BaseController;
use App\Modules\Agenda\Models\AppointmentModel;
use App\Modules\Core\Models\NoteTemplateModel;
use App\Modules\Core\Models\UserModel;
use App\Modules\Evolutions\Models\SessionEvolutionModel;
use App\Modules\Patients\Models\PatientModel;
use App\Modules\Patients\Models\PatientProfessionalModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class EvolutionController extends BaseController
{
    protected SessionEvolutionModel $evolutionModel;

    protected AppointmentModel $appointmentModel;

    protected PatientModel $patientModel;

    protected PatientProfessionalModel $patientProfessionalModel;

    protected UserModel $userModel;

    protected NoteTemplateModel $noteTemplateModel;

    public function __construct()
    {
        $this->evolutionModel    = new SessionEvolutionModel();
        $this->appointmentModel  = new AppointmentModel();
        $this->patientModel      = new PatientModel();
        $this->patientProfessionalModel = new PatientProfessionalModel();
        $this->userModel         = new UserModel();
        $this->noteTemplateModel = new NoteTemplateModel();
    }

    /**
     * Exibe a tela para registrar a evolucao de um agendamento.
     *
     * Caso ja exista evolucao para o agendamento (rascunho ou finalizada),
     * ela e carregada para permitir retomar a edicao ou exibir em modo
     * somente leitura.
     */
    public function create($appointmentId = null): string|RedirectResponse|ResponseInterface
    {
        $appointment = $this->appointmentModel->find((int) $appointmentId);

        if ($appointment === null) {
            return redirect()->to(site_url('agenda'))->with('error', 'Agendamento nao encontrado.');
        }

        if (! $this->canAccessEvolutionAppointment($appointment)) {
            return $this->response->setStatusCode(403)->setBody('Acesso negado. Você não tem permissão para registrar evolução deste agendamento.');
        }

        $patient      = $this->patientModel->find((int) ($appointment['patient_id'] ?? 0));
        $professional = $this->userModel->find((int) ($appointment['professional_id'] ?? 0));

        $evolution = $this->evolutionModel->forAppointment((int) $appointmentId);

        if ($evolution !== null) {
            $evolution = $this->evolutionModel->maskConfidentialText($evolution);
        }

        $finalizedByName = null;
        if ($evolution !== null && is_numeric($evolution['finalized_by'] ?? null)) {
            $finalizer = $this->userModel->find((int) $evolution['finalized_by']);
            $finalizedByName = $finalizer['name'] ?? null;
        }

        return view('App\Modules\Evolutions\Views\form', [
            'title'           => 'Registrar Evolucao',
            'appointment'     => $appointment,
            'patient'         => $patient,
            'professional'    => $professional,
            'noteTemplates'   => $this->noteTemplateModel->orderBy('name', 'ASC')->findAll(),
            'evolution'       => $evolution,
            'finalizedByName' => $finalizedByName,
        ]);
    }

    /**
     * Cria/atualiza a evolucao de um agendamento (upsert por appointment_id).
     *
     * Sem "finalize", salva como rascunho e NAO altera o status do
     * agendamento. Com "finalize", torna a evolucao imutavel e marca o
     * agendamento como 'realizado'.
     */
    public function store(): ResponseInterface
    {
        $payload       = $this->requestPayload();
        $appointmentId = (int) ($payload['appointment_id'] ?? 0);
        $finalize      = (bool) ($payload['finalize'] ?? false);

        if ($appointmentId <= 0) {
            return $this->jsonError(422, 'Agendamento invalido.');
        }

        $appointment = $this->appointmentModel->find($appointmentId);
        if ($appointment === null) {
            return $this->jsonError(404, 'Agendamento nao encontrado.');
        }

        if (! $this->canAccessEvolutionAppointment($appointment)) {
            return $this->jsonError(403, 'Acesso negado. Você não tem permissão para registrar evolução deste agendamento.');
        }

        $evolution = $this->evolutionModel->forAppointment($appointmentId);
        if ($this->evolutionModel->isFinalized($evolution)) {
            return $this->jsonError(403, 'Esta evolução já foi finalizada e não pode ser editada.');
        }

        return $this->persistEvolution($appointment, $payload, $finalize, $evolution);
    }

    /**
     * Edita um rascunho existente (via AJAX). Evolucoes finalizadas sao
     * imutaveis e retornam 403.
     */
    public function update($id = null): ResponseInterface
    {
        $evolution = $this->evolutionModel->find((int) $id);
        if ($evolution === null) {
            return $this->jsonError(404, 'Evolução não encontrada.');
        }

        if (! $this->canAccessEvolutionAppointment([
            'patient_id'      => $evolution['patient_id'] ?? 0,
            'professional_id' => $evolution['professional_id'] ?? 0,
        ])) {
            return $this->jsonError(403, 'Acesso negado. Você não tem permissão para editar esta evolução.');
        }

        if ($this->evolutionModel->isFinalized($evolution)) {
            return $this->jsonError(403, 'Esta evolução já foi finalizada e não pode ser editada.');
        }

        $payload       = $this->requestPayload();
        $evolutionText = trim((string) ($payload['evolution_text'] ?? ''));
        if ($evolutionText === '') {
            return $this->jsonError(422, 'O texto da evolucao e obrigatorio.');
        }

        $data = [
            'tenant_id'       => $evolution['tenant_id'] ?? null,
            'appointment_id'  => $evolution['appointment_id'] ?? 0,
            'patient_id'      => $evolution['patient_id'] ?? 0,
            'professional_id' => $evolution['professional_id'] ?? 0,
            'evolution_text'  => $evolutionText,
            'is_confidential' => (int) ($payload['is_confidential'] ?? ($evolution['is_confidential'] ?? 0)),
            'status'          => 'draft',
        ];

        if (! $this->validateData($data, $this->evolutionModel->getValidationRules(), $this->evolutionModel->getValidationMessages())) {
            return $this->jsonError(422, 'Dados invalidos.', $this->validator->getErrors());
        }

        if (! $this->evolutionModel->update((int) $evolution['id'], $data)) {
            return $this->jsonError(500, 'Falha ao salvar a evolucao.', $this->evolutionModel->errors());
        }

        return $this->response->setJSON([
            'success'      => true,
            'status'       => 'draft',
            'evolution_id' => (int) $evolution['id'],
            'csrf_hash'    => csrf_hash(),
        ]);
    }

    /**
     * Finaliza uma evolucao existente (imutavel apos a finalizacao).
     */
    public function finalize($id = null): ResponseInterface
    {
        $evolution = $this->evolutionModel->find((int) $id);
        if ($evolution === null) {
            return $this->jsonError(404, 'Evolução não encontrada.');
        }

        $appointment = $this->appointmentModel->find((int) ($evolution['appointment_id'] ?? 0));
        if ($appointment === null) {
            return $this->jsonError(404, 'Agendamento não encontrado.');
        }

        if (! $this->canAccessEvolutionAppointment($appointment)) {
            return $this->jsonError(403, 'Acesso negado. Você não tem permissão para finalizar esta evolução.');
        }

        if ($this->evolutionModel->isFinalized($evolution)) {
            return $this->jsonError(403, 'Esta evolução já foi finalizada e não pode ser editada.');
        }

        $payload = $this->requestPayload();
        $payload['evolution_text']  = trim((string) ($payload['evolution_text'] ?? ($evolution['evolution_text'] ?? '')));
        $payload['is_confidential'] = $payload['is_confidential'] ?? ($evolution['is_confidential'] ?? 0);

        return $this->persistEvolution($appointment, $payload, true, $evolution);
    }

    /**
     * Historico de evolucoes de um paciente (feed cronologico).
     */
    public function index($patientId = null): string|RedirectResponse|ResponseInterface
    {
        $patient = $this->patientModel->find((int) $patientId);

        if ($patient === null) {
            return redirect()->to(site_url('patients'))->with('error', 'Paciente nao encontrado.');
        }

        if (! can_own('patients.view', (int) $patientId)) {
            return $this->response->setStatusCode(403)->setBody('Acesso negado. Você não tem permissão para visualizar as evoluções deste paciente.');
        }

        return view('App\Modules\Evolutions\Views\index', [
            'title'      => 'Historico de Evolucoes',
            'patient'    => $patient,
            'evolutions' => $this->evolutionModel->forPatient((int) $patientId),
        ]);
    }

    /**
     * Persiste (insere ou atualiza) a evolucao aplicando as regras de
     * rascunho/finalizacao.
     *
     * @param array<string, mixed>      $appointment
     * @param array<string, mixed>      $payload
     * @param array<string, mixed>|null $existing
     */
    private function persistEvolution(array $appointment, array $payload, bool $finalize, ?array $existing): ResponseInterface
    {
        $evolutionText = trim((string) ($payload['evolution_text'] ?? ''));
        if ($evolutionText === '') {
            return $this->jsonError(422, 'O texto da evolucao e obrigatorio.');
        }

        $patientId      = (int) ($appointment['patient_id'] ?? 0);
        $professionalId = (int) ($appointment['professional_id'] ?? 0);

        if ($patientId <= 0 || $professionalId <= 0) {
            return $this->jsonError(422, 'O agendamento nao possui paciente ou profissional vinculado.');
        }

        $tenantId = is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;

        $data = [
            'tenant_id'       => $tenantId,
            'appointment_id'  => (int) ($appointment['id'] ?? 0),
            'patient_id'      => $patientId,
            'professional_id' => $professionalId,
            'evolution_text'  => $evolutionText,
            'is_confidential' => (int) ($payload['is_confidential'] ?? 0),
            'status'          => $finalize ? 'finalized' : 'draft',
        ];

        $finalizedByName = null;

        if ($finalize) {
            $finalizedBy = is_numeric(session('user_id')) ? (int) session('user_id') : null;
            $data['finalized_at'] = date('Y-m-d H:i:s');
            $data['finalized_by'] = $finalizedBy;

            if ($finalizedBy !== null) {
                $finalizer = $this->userModel->find($finalizedBy);
                $finalizedByName = $finalizer['name'] ?? null;
            }
        }

        if (! $this->validateData($data, $this->evolutionModel->getValidationRules(), $this->evolutionModel->getValidationMessages())) {
            return $this->jsonError(422, 'Dados invalidos.', $this->validator->getErrors());
        }

        if ($existing === null) {
            $evolutionId = $this->evolutionModel->insert($data);
            if ($evolutionId === false) {
                return $this->jsonError(500, 'Falha ao salvar a evolucao.', $this->evolutionModel->errors());
            }
        } else {
            $evolutionId = (int) $existing['id'];
            if (! $this->evolutionModel->update($evolutionId, $data)) {
                return $this->jsonError(500, 'Falha ao salvar a evolucao.', $this->evolutionModel->errors());
            }
        }

        // Auto-link sempre (inclusive rascunho), para o profissional ver o
        // paciente na listagem "Meus Pacientes".
        $this->autoLinkProfessional($patientId, $professionalId);

        // Somente a finalizacao conclui a sessao.
        if ($finalize) {
            $this->appointmentModel->update((int) ($appointment['id'] ?? 0), ['status' => 'realizado']);
        }

        $response = [
            'success'      => true,
            'status'       => $finalize ? 'finalized' : 'draft',
            'evolution_id' => (int) $evolutionId,
            'patient_id'   => $patientId,
            'csrf_hash'    => csrf_hash(),
        ];

        if ($finalize) {
            $response['finalized_at']      = $data['finalized_at'];
            $response['finalized_by_name'] = $finalizedByName;
        }

        return $this->response->setJSON($response);
    }

    /**
     * Indica se o usuário pode acessar a evolução de um agendamento:
     * é o profissional responsável OU pode visualizar o paciente.
     *
     * @param array<string, mixed> $appointment
     */
    private function canAccessEvolutionAppointment(array $appointment): bool
    {
        $userId = session('user_id');

        if (is_numeric($userId) && (int) ($appointment['professional_id'] ?? 0) === (int) $userId) {
            return true;
        }

        return can_own('patients.view', (int) ($appointment['patient_id'] ?? 0));
    }

    /**
     * Auto-link: vincula o profissional do agendamento ao paciente.
     */
    private function autoLinkProfessional(int $patientId, int $professionalId): void
    {
        if ($professionalId <= 0) {
            return;
        }

        $tenantId = is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;

        if (! $this->patientProfessionalModel->linkProfessional($patientId, $professionalId, $tenantId)) {
            log_message('error', 'Auto-link de evolução falhou para paciente ' . $patientId . ' e profissional ' . $professionalId . '.');
        }
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
