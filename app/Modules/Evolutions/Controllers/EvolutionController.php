<?php

namespace App\Modules\Evolutions\Controllers;

use App\Controllers\BaseController;
use App\Modules\Agenda\Models\AppointmentModel;
use App\Modules\Core\Models\NoteTemplateModel;
use App\Modules\Core\Models\UserModel;
use App\Modules\Evolutions\Models\SessionEvolutionModel;
use App\Modules\Patients\Models\PatientModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class EvolutionController extends BaseController
{
    protected SessionEvolutionModel $evolutionModel;

    protected AppointmentModel $appointmentModel;

    protected PatientModel $patientModel;

    protected UserModel $userModel;

    protected NoteTemplateModel $noteTemplateModel;

    public function __construct()
    {
        $this->evolutionModel    = new SessionEvolutionModel();
        $this->appointmentModel  = new AppointmentModel();
        $this->patientModel      = new PatientModel();
        $this->userModel         = new UserModel();
        $this->noteTemplateModel = new NoteTemplateModel();
    }

    /**
     * Exibe a tela para registrar a evolucao de um agendamento.
     */
    public function create($appointmentId = null): string|RedirectResponse
    {
        $appointment = $this->appointmentModel->find((int) $appointmentId);

        if ($appointment === null) {
            return redirect()->to(site_url('agenda'))->with('error', 'Agendamento nao encontrado.');
        }

        $patient      = $this->patientModel->find((int) ($appointment['patient_id'] ?? 0));
        $professional = $this->userModel->find((int) ($appointment['professional_id'] ?? 0));

        return view('App\Modules\Evolutions\Views\form', [
            'title'         => 'Registrar Evolucao',
            'appointment'   => $appointment,
            'patient'       => $patient,
            'professional'  => $professional,
            'noteTemplates' => $this->noteTemplateModel->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    /**
     * Salva a evolucao e marca o agendamento vinculado como 'realizado'.
     *
     * O paciente e o profissional sao derivados do agendamento (fonte da
     * verdade), nunca do payload enviado pelo cliente.
     */
    public function store(): ResponseInterface
    {
        $payload = $this->requestPayload();

        $appointmentId = (int) ($payload['appointment_id'] ?? 0);
        if ($appointmentId <= 0) {
            return $this->jsonError(422, 'Agendamento invalido.');
        }

        $appointment = $this->appointmentModel->find($appointmentId);
        if ($appointment === null) {
            return $this->jsonError(404, 'Agendamento nao encontrado.');
        }

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
            'appointment_id'  => $appointmentId,
            'patient_id'      => $patientId,
            'professional_id' => $professionalId,
            'evolution_text'  => $evolutionText,
        ];

        if (! $this->validateData($data, $this->evolutionModel->getValidationRules(), $this->evolutionModel->getValidationMessages())) {
            return $this->jsonError(422, 'Dados invalidos.', $this->validator->getErrors());
        }

        $evolutionId = $this->evolutionModel->insert($data);
        if ($evolutionId === false) {
            return $this->jsonError(500, 'Falha ao salvar a evolucao.', $this->evolutionModel->errors());
        }

        // Regra de negocio: ao registrar a evolucao, a sessao foi realizada.
        $this->appointmentModel->update($appointmentId, ['status' => 'realizado']);

        return $this->response->setJSON([
            'success'      => true,
            'message'      => 'Evolucao registrada com sucesso.',
            'evolution_id' => (int) $evolutionId,
            'patient_id'   => $patientId,
            'csrf_hash'    => csrf_hash(),
        ]);
    }

    /**
     * Historico de evolucoes de um paciente (feed cronologico).
     */
    public function index($patientId = null): string|RedirectResponse
    {
        $patient = $this->patientModel->find((int) $patientId);

        if ($patient === null) {
            return redirect()->to(site_url('patients'))->with('error', 'Paciente nao encontrado.');
        }

        return view('App\Modules\Evolutions\Views\index', [
            'title'      => 'Historico de Evolucoes',
            'patient'    => $patient,
            'evolutions' => $this->evolutionModel->forPatient((int) $patientId),
        ]);
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
