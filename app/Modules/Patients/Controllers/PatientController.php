<?php

namespace App\Modules\Patients\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\HealthInsuranceModel;
use App\Modules\Core\Models\UnitModel;
use App\Modules\Evolutions\Models\SessionEvolutionModel;
use App\Modules\Patients\Models\GuardianModel;
use App\Modules\Patients\Models\PatientGuardianModel;
use App\Modules\Patients\Models\PatientMilestoneModel;
use App\Modules\Patients\Models\PatientModel;
use App\Modules\Patients\Models\PatientPostModel;
use App\Modules\Protocols\Models\ProtocolModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class PatientController extends BaseController
{
    protected PatientModel $patientModel;

    protected GuardianModel $guardianModel;

    protected PatientGuardianModel $patientGuardianModel;

    protected UnitModel $unitModel;

    protected HealthInsuranceModel $healthInsuranceModel;

    protected ProtocolModel $protocolModel;

    protected SessionEvolutionModel $evolutionModel;

    protected PatientMilestoneModel $milestoneModel;

    protected PatientPostModel $postModel;

    public function __construct()
    {
        $this->patientModel         = new PatientModel();
        $this->guardianModel        = new GuardianModel();
        $this->patientGuardianModel = new PatientGuardianModel();
        $this->unitModel            = new UnitModel();
        $this->healthInsuranceModel = new HealthInsuranceModel();
        $this->protocolModel        = new ProtocolModel();
        $this->evolutionModel       = new SessionEvolutionModel();
        $this->milestoneModel       = new PatientMilestoneModel();
        $this->postModel            = new PatientPostModel();
    }

    /**
     * Lista os pacientes do tenant com busca e paginacao.
     */
    public function index(): string
    {
        $search = trim((string) $this->request->getGet('search'));

        $patients = $this->patientModel->paginateWithCatalogs($search);

        return view('App\Modules\Patients\Views\index', [
            'title'     => 'Pacientes',
            'patients'  => $patients,
            'protocols' => $this->protocolModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
            'pager'     => $this->patientModel->pager,
            'search'    => $search,
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
            'title'            => 'Novo Paciente',
            'patient'          => null,
            'guardians'        => $guardians,
            'units'            => $this->unitModel->orderBy('name', 'ASC')->findAll(),
            'healthInsurances' => $this->healthInsuranceModel->orderBy('name', 'ASC')->findAll(),
            'evolutions'       => [],
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

        $patientId = $this->patientModel->insert($data);

        if ($patientId === false) {
            return redirect()->back()->withInput()->with('errors', $this->patientModel->errors());
        }

        $this->saveGuardians((int) $patientId, $this->request->getPost('guardians') ?? []);

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
            'postTypes'        => PatientPostModel::POST_TYPES,
            'milestoneIcons'   => PatientMilestoneModel::MILESTONE_ICONS,
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

        $data = $this->patientDataFromRequest();

        if (! $this->validateData($data, $this->patientModel->getValidationRules(), $this->patientModel->getValidationMessages())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if (! $this->patientModel->update((int) $id, $data)) {
            return redirect()->back()->withInput()->with('errors', $this->patientModel->errors());
        }

        $this->saveGuardians((int) $id, $this->request->getPost('guardians') ?? []);

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
