<?php

namespace App\Modules\Patients\Controllers;

use App\Controllers\BaseController;
use App\Modules\Agenda\Models\AppointmentModel;
use App\Modules\Assessments\Models\PatientAssessmentModel;
use App\Modules\Core\Models\HealthInsuranceModel;
use App\Modules\Core\Models\UnitModel;
use App\Modules\Core\Models\UserModel;
use App\Modules\Evolutions\Models\SessionEvolutionModel;
use App\Modules\Patients\Models\GuardianModel;
use App\Modules\Patients\Models\PatientGuardianModel;
use App\Modules\Patients\Models\PatientMilestoneModel;
use App\Modules\Patients\Models\PatientModel;
use App\Modules\Patients\Models\PatientPostModel;
use App\Modules\Patients\Models\PatientProfessionalModel;
use App\Modules\Protocols\Models\ProtocolModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class PatientController extends BaseController
{
    protected PatientModel $patientModel;

    protected AppointmentModel $appointmentModel;

    protected GuardianModel $guardianModel;

    protected PatientGuardianModel $patientGuardianModel;

    protected UnitModel $unitModel;

    protected HealthInsuranceModel $healthInsuranceModel;

    protected ProtocolModel $protocolModel;

    protected PatientAssessmentModel $assessmentModel;

    protected SessionEvolutionModel $evolutionModel;

    protected PatientMilestoneModel $milestoneModel;

    protected PatientPostModel $postModel;

    protected PatientProfessionalModel $patientProfessionalModel;

    protected UserModel $userModel;

    public function __construct()
    {
        $this->patientModel         = new PatientModel();
        $this->appointmentModel     = new AppointmentModel();
        $this->guardianModel        = new GuardianModel();
        $this->patientGuardianModel = new PatientGuardianModel();
        $this->unitModel            = new UnitModel();
        $this->healthInsuranceModel = new HealthInsuranceModel();
        $this->protocolModel        = new ProtocolModel();
        $this->assessmentModel      = new PatientAssessmentModel();
        $this->evolutionModel       = new SessionEvolutionModel();
        $this->milestoneModel       = new PatientMilestoneModel();
        $this->postModel               = new PatientPostModel();
        $this->patientProfessionalModel = new PatientProfessionalModel();
        $this->userModel               = new UserModel();
    }

    /**
     * Lista os pacientes do tenant com busca e paginacao.
     */
    public function index(): string|RedirectResponse
    {
        $scope = $this->patientViewScope();

        if ($scope === 'none') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado. Você não tem permissão para visualizar pacientes.');
        }

        $canViewAll = can('patients.view.any');
        $viewAll    = $canViewAll && $this->request->getGet('view') === 'all';

        $search         = trim((string) $this->request->getGet('search'));
        $showInactive   = (string) $this->request->getGet('show_inactive') === '1';
        $professionalId = $viewAll ? null : (int) session('user_id');

        $patients = $this->patientModel->paginateWithCatalogs($search, $showInactive, $professionalId);
        $patients = $this->withPatientPermissions($patients);

        return view('App\Modules\Patients\Views\index', [
            'title'        => 'Pacientes',
            'patients'     => $patients,
            'protocols'    => $this->protocolModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
            'pager'        => $this->patientModel->pager,
            'search'       => $search,
            'showInactive' => $showInactive,
            'canViewAll'   => $canViewAll,
            'viewAll'      => $viewAll,
        ]);
    }

    /**
     * Anexa a cada paciente as flags de permissao do usuario logado
     * (pode editar, desativar e iniciar avaliacao), usadas para esconder
     * acoes indevidas na listagem.
     *
     * @param array<int, array<string, mixed>> $patients
     *
     * @return array<int, array<string, mixed>>
     */
    private function withPatientPermissions(array $patients): array
    {
        $canEditAny       = can('patients.edit.any');
        $canDeactivateAny = can('patients.deactivate.any');
        $canAssessAny     = can('assessments.edit.any');

        $canEditOwn       = can('patients.edit.own');
        $canDeactivateOwn = can('patients.deactivate.own');
        $canAssessOwn     = can('assessments.edit.own');

        $linkedIds  = [];
        $primaryIds = [];

        $userId = session('user_id');

        if (is_numeric($userId) && ($canEditOwn || $canDeactivateOwn || $canAssessOwn)) {
            $rows = $this->patientProfessionalModel
                ->select('patient_id, is_primary')
                ->where('user_id', (int) $userId)
                ->where('deleted_at', null)
                ->findAll();

            foreach ($rows as $row) {
                $patientId = (int) ($row['patient_id'] ?? 0);

                if ($patientId <= 0) {
                    continue;
                }

                $linkedIds[] = $patientId;

                if ((int) ($row['is_primary'] ?? 0) === 1) {
                    $primaryIds[] = $patientId;
                }
            }

            $linkedIds  = array_values(array_unique($linkedIds));
            $primaryIds = array_values(array_unique($primaryIds));
        }

        foreach ($patients as $index => $patient) {
            $patientId = (int) ($patient['id'] ?? 0);
            $isLinked  = in_array($patientId, $linkedIds, true);
            $isPrimary = in_array($patientId, $primaryIds, true);

            $patients[$index]['can_edit']       = $canEditAny || ($canEditOwn && $isLinked);
            $patients[$index]['can_deactivate'] = $canDeactivateAny || ($canDeactivateOwn && $isLinked);
            $patients[$index]['can_assess']     = $canAssessAny || ($canAssessOwn && $isPrimary);
        }

        return $patients;
    }

    /**
     * Escopo de visualizacao de pacientes do usuario logado.
     *
     * @return 'any'|'own'|'none'
     */
    private function patientViewScope(): string
    {
        if (can('patients.view.any')) {
            return 'any';
        }

        if (can('patients.view.own')) {
            return 'own';
        }

        return 'none';
    }

    /**
     * Busca rapida de pacientes (JSON) para o Command Palette do layout.
     *
     * Retorna os top resultados com as URLs de acao ja montadas.
     */
    public function search(): ResponseInterface
    {
        $q = trim((string) $this->request->getGet('q'));

        if (strlen($q) < 2) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON(['success' => false, 'message' => 'Informe ao menos 2 caracteres para buscar.']);
        }

        $scope = $this->patientViewScope();

        if ($scope === 'none') {
            return $this->response->setJSON(['success' => true, 'results' => []]);
        }

        $professionalId = $scope === 'own' ? (int) session('user_id') : null;

        $patients = $this->patientModel->quickSearch($q, 8, $professionalId);

        $patientIds       = array_map(static fn (array $p): int => (int) $p['id'], $patients);
        $nextAppointments = $patientIds === [] ? [] : $this->appointmentModel->nextAppointmentIdForPatients($patientIds);

        $results = [];

        foreach ($patients as $patient) {
            $patientId         = (int) $patient['id'];
            $nextAppointmentId = $nextAppointments[$patientId] ?? null;

            $results[] = [
                'id'                  => $patientId,
                'name'                => (string) ($patient['name'] ?? ''),
                'cpf'                 => (string) ($patient['cpf'] ?? ''),
                'birth_date'          => (string) ($patient['birth_date'] ?? ''),
                'color'               => (string) ($patient['color'] ?? '#3b82f6'),
                'is_active'           => (int) ($patient['is_active'] ?? 1),
                'unit_name'           => (string) ($patient['unit_name'] ?? ''),
                'next_appointment_id' => $nextAppointmentId,
                'profile_url'         => site_url('patients/show/' . $patientId),
                'assessment_url'      => site_url('patients/edit/' . $patientId . '?tab=avaliacoes'),
                'agenda_url'          => site_url('agenda?patient_id=' . $patientId),
                'evolution_url'       => $nextAppointmentId !== null
                    ? site_url('evolutions/create/' . $nextAppointmentId)
                    : null,
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Exibe o formulario de criacao.
     */
    public function create(): string
    {
        $guardians = $this->guardiansFromOldInput();
        if ($guardians === []) {
            $guardians = [$this->emptyGuardianRow()];
        }

        return view('App\Modules\Patients\Views\form', [
            'title'                    => 'Novo Paciente',
            'patient'                  => null,
            'guardians'                => $guardians,
            'units'                    => $this->unitModel->orderBy('name', 'ASC')->findAll(),
            'healthInsurances'         => $this->healthInsuranceModel->orderBy('name', 'ASC')->findAll(),
            'evolutions'               => [],
            'professionals'            => $this->userModel->professionals(),
            'selectedProfessionalIds'  => $this->professionalIdsFromOldInput(),
            'primaryProfessionalId'    => $this->primaryProfessionalIdFromOldInput(),
        ]);
    }
    /**
     * Persiste um novo paciente.
     */
    public function store(): RedirectResponse
    {
        $data = $this->patientDataFromRequest();

        if (! $this->validateData($data, $this->patientModel->getValidationRules(), $this->patientModel->getValidationMessages())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $tenantId = is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
        if ($tenantId !== null) {
            $data['tenant_id'] = $tenantId;
        }

        // Sprint 1: atribui automaticamente uma cor da paleta no cadastro.
        $data['color'] = $this->patientModel->assignColorForTenant($tenantId);

        $patientId = $this->patientModel->insert($data);

        if ($patientId === false) {
            return redirect()->back()->withInput()->with('errors', $this->patientModel->errors());
        }

        $this->saveGuardians((int) $patientId, $this->request->getPost('guardians') ?? []);
        $this->saveProfessionals((int) $patientId);

        // Automacao: cria o marco inicial da jornada do paciente.
        if ($this->milestoneModel->insert([
            'tenant_id'  => $tenantId,
            'patient_id' => (int) $patientId,
            'title'      => 'Início do Tratamento',
            'event_date' => date('Y-m-d'),
        ]) === false) {
            log_message('error', 'Falha ao criar o marco "Início do Tratamento" para o paciente ' . (int) $patientId . '.');
        }

        return redirect()->to(site_url('patients'))->with('success', 'Paciente criado com sucesso.');
    }

    /**
     * Exibe o formulario de edicao.
     */
    public function edit($id = null): string|RedirectResponse
    {
        $patient = $this->patientModel->find((int) $id);

        if ($patient === null) {
            return redirect()->to(site_url('patients'))->with('error', 'Paciente nao encontrado.');
        }

        if (! can_own('patients.edit', (int) $id)) {
            return redirect()->to(site_url('patients'))->with('error', 'Acesso negado. Você não tem permissão para editar este paciente.');
        }

        $guardians = $this->guardiansFromOldInput();
        if ($guardians === []) {
            $guardians = $this->guardiansFromDatabase((int) $id);
        }
        if ($guardians === []) {
            $guardians = [$this->emptyGuardianRow()];
        }

        return view('App\Modules\Patients\Views\form', [
            'title'            => 'Editar Paciente',
            'patient'          => $patient,
            'guardians'        => $guardians,
            'units'            => $this->unitModel->orderBy('name', 'ASC')->findAll(),
            'healthInsurances' => $this->healthInsuranceModel->orderBy('name', 'ASC')->findAll(),
            'evolutions'       => $this->evolutionModel->forPatient((int) $id),
            'posts'            => $this->postModel->forPatient((int) $id),
            'milestones'       => $this->milestoneModel->forPatient((int) $id),
            'assessments'      => $this->assessmentModel->forPatient((int) $id),
            'protocols'               => $this->protocolModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
            'postTypes'               => PatientPostModel::POST_TYPES,
            'milestoneIcons'          => PatientMilestoneModel::MILESTONE_ICONS,
            'professionals'           => $this->userModel->professionals(),
            'selectedProfessionalIds' => $this->professionalIdsForEdit((int) $id),
            'primaryProfessionalId'   => $this->primaryProfessionalIdForEdit((int) $id),
            'canManageAssessments'    => can_own_primary('assessments.edit', (int) $id) || can_own_primary('assessments.finalize', (int) $id),
        ]);
    }

    /**
     * Exibe o perfil do paciente em modo somente leitura.
     */
    public function show($id = null): string|RedirectResponse
    {
        $patient = $this->patientModel->find((int) $id);

        if ($patient === null) {
            return redirect()->to(site_url('patients'))->with('error', 'Paciente nao encontrado.');
        }

        if (! can_own('patients.view', (int) $id)) {
            return redirect()->to(site_url('patients'))->with('error', 'Acesso negado. Você não tem permissão para visualizar este paciente.');
        }

        $unit            = $this->unitModel->find((int) ($patient['unit_id'] ?? 0));
        $healthInsurance = $this->healthInsuranceModel->find((int) ($patient['health_insurance_id'] ?? 0));

        return view('App\Modules\Patients\Views\show', [
            'title'                 => 'Perfil do Paciente',
            'patient'               => $patient,
            'unitName'              => $unit['name'] ?? null,
            'insuranceName'         => $healthInsurance['name'] ?? null,
            'guardians'             => $this->guardiansFromDatabase((int) $id),
            'professionals'         => $this->userModel->professionals(),
            'professionalIds'       => $this->patientProfessionalModel->professionalIdsForPatient((int) $id),
            'primaryProfessionalId' => $this->patientProfessionalModel->primaryProfessionalIdForPatient((int) $id),
            'posts'                 => $this->postModel->forPatient((int) $id),
            'milestones'            => $this->milestoneModel->forPatient((int) $id),
            'assessments'           => $this->assessmentModel->forPatient((int) $id),
            'evolutions'            => $this->evolutionModel->forPatient((int) $id),
            'postTypes'             => PatientPostModel::POST_TYPES,
            'canEdit'               => can_own('patients.edit', (int) $id),
            'canManageAssessments'  => can_own_primary('assessments.edit', (int) $id) || can_own_primary('assessments.finalize', (int) $id),
        ]);
    }

    /**
     * Atualiza um paciente existente.
     */
    public function update($id = null): RedirectResponse
    {
        $patient = $this->patientModel->find((int) $id);

        if ($patient === null) {
            return redirect()->to(site_url('patients'))->with('error', 'Paciente nao encontrado.');
        }

        if (! can_own('patients.edit', (int) $id)) {
            return redirect()->to(site_url('patients'))->with('error', 'Acesso negado. Você não tem permissão para editar este paciente.');
        }

        $data = $this->patientDataFromRequest();

        if (! $this->validateData($data, $this->patientModel->getValidationRules(), $this->patientModel->getValidationMessages())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Se a cor nao veio no formulario (campo ausente), preserva a atual.
        if (($data['color'] ?? null) === null) {
            unset($data['color']);
        }

        if (! $this->patientModel->update((int) $id, $data)) {
            return redirect()->back()->withInput()->with('errors', $this->patientModel->errors());
        }

        $this->saveGuardians((int) $id, $this->request->getPost('guardians') ?? []);
        $this->saveProfessionals((int) $id);

        return redirect()->to(site_url('patients'))->with('success', 'Paciente atualizado com sucesso.');
    }

    /**
     * Alterna o status ativo/inativo via AJAX.
     */
    public function toggleStatus($id = null): ResponseInterface
    {
        $patient = $this->patientModel->find((int) $id);

        if ($patient === null) {
            return $this->response
                ->setJSON(['success' => false, 'message' => 'Paciente nao encontrado.', 'csrf_hash' => csrf_hash()])
                ->setStatusCode(404);
        }

        if (! can_own('patients.deactivate', (int) $id)) {
            return $this->response
                ->setJSON(['success' => false, 'message' => 'Acesso negado. Você não tem permissão para alterar este paciente.', 'csrf_hash' => csrf_hash()])
                ->setStatusCode(403);
        }

        $newStatus = (int) ($patient['is_active'] ?? 0) === 1 ? 0 : 1;

        if (! $this->patientModel->update((int) $id, ['is_active' => $newStatus])) {
            return $this->response->setJSON([
                'success'   => false,
                'message'   => 'Falha ao atualizar o status do paciente.',
                'csrf_hash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'success'   => true,
            'is_active' => $newStatus,
            'message'   => $newStatus === 1 ? 'Paciente ativado.' : 'Paciente inativado.',
            'csrf_hash' => csrf_hash(),
        ]);
    }
    /**
     * Normaliza os dados do paciente vindos do formulario.
     *
     * @return array<string, mixed>
     */
    private function patientDataFromRequest(): array
    {
        $data = $this->request->getPost([
            'name',
            'birth_date',
            'cpf',
            'sex',
            'is_active',
            'diagnosis',
            'support_level',
            'allergies',
            'unit_id',
            'health_insurance_id',
            'insurance_number',
            'tags',
            'zip_code',
            'street',
            'number',
            'complement',
            'neighborhood',
            'city',
            'state',
            'color',
        ]);

        $data['cpf']       = $this->onlyDigits($data['cpf'] ?? '');
        $data['zip_code']  = $this->onlyDigits($data['zip_code'] ?? '');
        $data['is_active'] = (int) ($data['is_active'] ?? 1);

        $required = ['name', 'birth_date', 'unit_id'];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $data[$key] = $value;
            }

            if ($value === '' && ! in_array($key, $required, true)) {
                $data[$key] = null;
            }
        }

        $data['unit_id']             = $this->integerOrNull($data['unit_id'] ?? null);
        $data['health_insurance_id'] = $this->integerOrNull($data['health_insurance_id'] ?? null);

        return $data;
    }

    /**
     * Mantem apenas digitos de um valor.
     */
    private function onlyDigits(mixed $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits === '' ? null : $digits;
    }

    /**
     * Converte um valor para inteiro ou null quando vazio.
     */
    private function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
    /**
     * Persiste os responsaveis e seus vinculos com o paciente.
     *
     * @param array<int, array<string, mixed>> $guardianRows
     */
    private function saveGuardians(int $patientId, array $guardianRows): void
    {
        $tenantId    = is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
        $existingIds = [];

        foreach ($guardianRows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $guardianData = [
                'tenant_id'    => $tenantId,
                'name'         => $name,
                'relationship' => trim((string) ($row['relationship'] ?? '')) ?: null,
                'cpf'          => $this->onlyDigits($row['cpf'] ?? ''),
                'phone'        => $this->onlyDigits($row['phone'] ?? ''),
                'email'        => trim((string) ($row['email'] ?? '')) ?: null,
            ];

            $guardianId = (int) ($row['id'] ?? 0);

            if ($guardianId > 0) {
                $existingGuardian = $this->guardianModel->find($guardianId);
                if ($existingGuardian === null) {
                    continue;
                }

                $this->guardianModel->update($guardianId, $guardianData);
                $existingIds[] = $guardianId;
            } else {
                $guardianId = $this->guardianModel->insert($guardianData);
                if ($guardianId === false) {
                    continue;
                }

                $guardianId = (int) $guardianId;
                $existingIds[] = $guardianId;
            }

            $pivot = $this->patientGuardianModel
                ->where('patient_id', $patientId)
                ->where('guardian_id', $guardianId)
                ->first();

            $pivotData = [
                'tenant_id'    => $tenantId,
                'patient_id'   => $patientId,
                'guardian_id'  => $guardianId,
                'is_primary'   => (int) ($row['is_primary'] ?? 0),
                'is_financial' => (int) ($row['is_financial'] ?? 0),
            ];

            if ($pivot === null) {
                $this->patientGuardianModel->insert($pivotData);
            } else {
                $this->patientGuardianModel->update((int) $pivot['id'], $pivotData);
            }
        }

        if ($existingIds === []) {
            $this->patientGuardianModel->where('patient_id', $patientId)->delete();

            return;
        }

        $this->patientGuardianModel
            ->where('patient_id', $patientId)
            ->whereNotIn('guardian_id', $existingIds)
            ->delete();
    }
    /**
     * Persiste os profissionais vinculados ao paciente.
     */
    private function saveProfessionals(int $patientId): void
    {
        $tenantId = is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;

        $this->patientProfessionalModel->syncProfessionals(
            $patientId,
            $tenantId,
            $this->professionalIdsFromRequest(),
            $this->primaryProfessionalIdFromRequest()
        );
    }

    /**
     * Ids de profissionais selecionados no formulario atual.
     *
     * @return list<int>
     */
    private function professionalIdsFromRequest(): array
    {
        return $this->normalizeProfessionalIds($this->request->getPost('professional_ids'));
    }

    /**
     * Ids de profissionais vindos do input anterior (validacao que falhou).
     *
     * @return list<int>
     */
    private function professionalIdsFromOldInput(): array
    {
        return $this->normalizeProfessionalIds(old('professional_ids'));
    }

    /**
     * @return list<int>
     */
    private function normalizeProfessionalIds(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
    }

    private function primaryProfessionalIdFromRequest(): ?int
    {
        return $this->normalizePrimaryProfessionalId($this->request->getPost('primary_professional_id'));
    }

    private function primaryProfessionalIdFromOldInput(): ?int
    {
        return $this->normalizePrimaryProfessionalId(old('primary_professional_id'));
    }

    private function normalizePrimaryProfessionalId(mixed $id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        return (int) $id > 0 ? (int) $id : null;
    }

    /**
     * Profissionais selecionados para exibicao no formulario de edicao.
     *
     * @return list<int>
     */
    private function professionalIdsForEdit(int $patientId): array
    {
        $ids = $this->professionalIdsFromOldInput();

        return $ids !== [] ? $ids : $this->patientProfessionalModel->professionalIdsForPatient($patientId);
    }

    private function primaryProfessionalIdForEdit(int $patientId): ?int
    {
        if (old('primary_professional_id') !== null) {
            return $this->primaryProfessionalIdFromOldInput();
        }

        return $this->patientProfessionalModel->primaryProfessionalIdForPatient($patientId);
    }

    /**
     * Reconstroi a lista de responsaveis a partir do input anterior
     * (usado quando a validacao falha).
     *
     * @return array<int, array<string, mixed>>
     */
    private function guardiansFromOldInput(): array
    {
        $rows = old('guardians');

        if (! is_array($rows)) {
            return [];
        }

        $guardians = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if ((int) ($row['id'] ?? 0) === 0 && trim((string) ($row['name'] ?? '')) === '') {
                continue;
            }

            $guardians[] = [
                'id'           => (int) ($row['id'] ?? 0),
                'name'         => (string) ($row['name'] ?? ''),
                'relationship' => (string) ($row['relationship'] ?? ''),
                'cpf'          => (string) ($row['cpf'] ?? ''),
                'phone'        => (string) ($row['phone'] ?? ''),
                'email'        => (string) ($row['email'] ?? ''),
                'is_primary'   => (int) ($row['is_primary'] ?? 0),
                'is_financial' => (int) ($row['is_financial'] ?? 0),
            ];
        }

        return $guardians;
    }

    /**
     * Busca os responsaveis ja vinculados ao paciente.
     *
     * @return array<int, array<string, mixed>>
     */
    private function guardiansFromDatabase(int $patientId): array
    {
        $pivots = $this->patientGuardianModel->where('patient_id', $patientId)->findAll();

        $guardians = [];

        foreach ($pivots as $pivot) {
            $guardian = $this->guardianModel->find((int) $pivot['guardian_id']);
            if ($guardian === null) {
                continue;
            }

            $guardians[] = [
                'id'           => (int) $guardian['id'],
                'name'         => (string) $guardian['name'],
                'relationship' => (string) ($guardian['relationship'] ?? ''),
                'cpf'          => (string) ($guardian['cpf'] ?? ''),
                'phone'        => (string) ($guardian['phone'] ?? ''),
                'email'        => (string) ($guardian['email'] ?? ''),
                'is_primary'   => (int) ($pivot['is_primary'] ?? 0),
                'is_financial' => (int) ($pivot['is_financial'] ?? 0),
            ];
        }

        return $guardians;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyGuardianRow(): array
    {
        return [
            'id'           => 0,
            'name'         => '',
            'relationship' => '',
            'cpf'          => '',
            'phone'        => '',
            'email'        => '',
            'is_primary'   => 0,
            'is_financial' => 0,
        ];
    }




}
