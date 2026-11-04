<?php

namespace App\Modules\Agenda\Controllers;

use App\Controllers\BaseController;
use App\Modules\Agenda\Models\CityModel;
use App\Modules\Agenda\Models\HolidayModel;
use App\Modules\Agenda\Services\HolidayService;
use CodeIgniter\HTTP\ResponseInterface;

class HolidayController extends BaseController
{
    protected HolidayModel $holidayModel;

    protected CityModel $cityModel;

    protected HolidayService $holidayService;

    public function __construct()
    {
        $this->holidayModel   = new HolidayModel();
        $this->cityModel      = new CityModel();
        $this->holidayService = new HolidayService($this->holidayModel);
    }

    /**
     * Tela de cadastro de cidade, feriados nacionais e feriados locais.
     */
    public function index(): string
    {
        $tenantId = $this->currentTenantId();
        $year     = (int) date('Y');
        $config   = config('Agenda');
        $badges   = $config->holidayBadges ?? [];

        return view('App\Modules\Agenda\Views\settings\holidays', [
            'title'        => 'Feriados e Cidade',
            'cities'       => $this->cityModel->forTenant($tenantId),
            'national'     => $this->holidayModel->nationalForYear($year),
            'manual'       => $this->holidayModel->manualForTenant($tenantId),
            'badges'       => $badges,
            'manualScopes' => ['state', 'municipal', 'closure'],
            'states'       => $this->states(),
        ]);
    }

    /**
     * Cadastra uma cidade para o tenant.
     */
    public function storeCity(): ResponseInterface
    {
        $data = $this->cityDataFromRequest();

        if (! $this->validateData($data, $this->cityModel->getValidationRules())) {
            return $this->respondError($this->validator->getErrors());
        }

        $tenantId          = $this->currentTenantId();
        $data['tenant_id'] = $tenantId;
        $data['is_active'] = 1;

        if ($this->cityModel->insert($data) === false) {
            return $this->respondError($this->cityModel->errors());
        }

        return $this->respondSuccess('Cidade cadastrada com sucesso.', [
            'cities' => $this->serializeCities($this->cityModel->forTenant($tenantId)),
        ]);
    }

    /**
     * Atualiza uma cidade do tenant.
     */
    public function updateCity($id = null): ResponseInterface
    {
        $city = $this->cityModel->forTenantById($this->currentTenantId(), (int) $id);

        if ($city === null) {
            return $this->respondError([], 'Cidade não encontrada.', 404);
        }

        $data = $this->cityDataFromRequest();

        if (! $this->validateData($data, $this->cityModel->getValidationRules())) {
            return $this->respondError($this->validator->getErrors());
        }

        if (! $this->cityModel->update((int) $city['id'], $data)) {
            return $this->respondError($this->cityModel->errors());
        }

        return $this->respondSuccess('Cidade atualizada com sucesso.', [
            'cities' => $this->serializeCities($this->cityModel->forTenant($this->currentTenantId())),
        ]);
    }

    /**
     * Remove uma cidade do tenant (soft delete).
     */
    public function deleteCity($id = null): ResponseInterface
    {
        $city = $this->cityModel->forTenantById($this->currentTenantId(), (int) $id);

        if ($city === null) {
            return $this->respondError([], 'Cidade não encontrada.', 404);
        }

        if (! $this->cityModel->delete((int) $city['id'])) {
            return $this->respondError($this->cityModel->errors(), 'Não foi possível excluir a cidade.');
        }

        return $this->respondSuccess('Cidade excluída com sucesso.', [
            'cities' => $this->serializeCities($this->cityModel->forTenant($this->currentTenantId())),
        ]);
    }

    /**
     * Dispara a sincronização de feriados nacionais (BrasilAPI).
     */
    public function syncNational(): ResponseInterface
    {
        $year = (int) ($this->request->getPost('year') ?? date('Y'));

        try {
            $count = $this->holidayService->syncNational($year);
        } catch (\Throwable $e) {
            return $this->respondError([], $e->getMessage(), 502);
        }

        return $this->respondSuccess(sprintf('Sincronização concluída: %d feriado(s) nacionais.', $count), [
            'national' => $this->serializeHolidays($this->holidayModel->nationalForYear($year)),
        ]);
    }

    /**
     * Cadastra um feriado local / data de fechamento.
     */
    public function store(): ResponseInterface
    {
        $data = $this->holidayDataFromRequest();

        if (! $this->validateData($data, $this->holidayModel->getValidationRules())) {
            return $this->respondError($this->validator->getErrors());
        }

        $data['tenant_id'] = $this->currentTenantId();

        if (! $this->holidayModel->insertManual($data)) {
            return $this->respondError($this->holidayModel->errors());
        }

        return $this->respondSuccess('Feriado cadastrado com sucesso.', [
            'manual' => $this->serializeHolidays($this->holidayModel->manualForTenant($this->currentTenantId())),
        ]);
    }

    /**
     * Atualiza um feriado local / data de fechamento.
     */
    public function update($id = null): ResponseInterface
    {
        $holiday = $this->holidayModel->manualById((int) $id, $this->currentTenantId());

        if ($holiday === null) {
            return $this->respondError([], 'Feriado não encontrado.', 404);
        }

        $data = $this->holidayDataFromRequest();

        if (! $this->validateData($data, $this->holidayModel->getValidationRules())) {
            return $this->respondError($this->validator->getErrors());
        }

        if (! $this->holidayModel->updateManual((int) $holiday['id'], $data)) {
            return $this->respondError($this->holidayModel->errors());
        }

        return $this->respondSuccess('Feriado atualizado com sucesso.', [
            'manual' => $this->serializeHolidays($this->holidayModel->manualForTenant($this->currentTenantId())),
        ]);
    }

    /**
     * Remove um feriado local / data de fechamento.
     */
    public function destroy($id = null): ResponseInterface
    {
        $holiday = $this->holidayModel->manualById((int) $id, $this->currentTenantId());

        if ($holiday === null) {
            return $this->respondError([], 'Feriado não encontrado.', 404);
        }

        if (! $this->holidayModel->deleteManual((int) $holiday['id'])) {
            return $this->respondError([], 'Não foi possível excluir o feriado.');
        }

        return $this->respondSuccess('Feriado excluído com sucesso.', [
            'manual' => $this->serializeHolidays($this->holidayModel->manualForTenant($this->currentTenantId())),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cityDataFromRequest(): array
    {
        $code = trim((string) $this->request->getPost('city_code'));

        return [
            'city_name' => trim((string) $this->request->getPost('city_name')),
            'state'     => strtoupper(trim((string) $this->request->getPost('state'))),
            'city_code' => $code !== '' ? $code : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function holidayDataFromRequest(): array
    {
        $scope  = (string) $this->request->getPost('scope');
        $config = config('Agenda');
        $badges = $config->holidayBadges ?? [];
        $badge  = $badges[$scope] ?? ['label' => 'Feriado', 'color' => '#f59e0b'];

        return [
            'name'  => trim((string) $this->request->getPost('name')),
            'date'  => trim((string) $this->request->getPost('date')),
            'scope' => $scope,
            'badge' => $badge['label'],
            'color' => $badge['color'],
        ];
    }


    /**
     * @param list<array<string, mixed>> $cities
     *
     * @return list<array<string, mixed>>
     */
    private function serializeCities(array $cities): array
    {
        return array_map(static fn (array $city): array => [
            'id'        => (int) $city['id'],
            'city_name' => (string) ($city['city_name'] ?? ''),
            'state'     => (string) ($city['state'] ?? ''),
            'city_code' => (string) ($city['city_code'] ?? ''),
        ], $cities);
    }

    /**
     * @param list<array<string, mixed>> $holidays
     *
     * @return list<array<string, mixed>>
     */
    private function serializeHolidays(array $holidays): array
    {
        return array_map(static fn (array $holiday): array => [
            'id'    => (int) $holiday['id'],
            'name'  => (string) ($holiday['name'] ?? ''),
            'date'  => (string) ($holiday['date'] ?? ''),
            'scope' => (string) ($holiday['scope'] ?? ''),
            'badge' => (string) ($holiday['badge'] ?? ''),
            'color' => (string) ($holiday['color'] ?? '#f59e0b'),
        ], $holidays);
    }

    /**
     * @return array<string, string>
     */
    private function states(): array
    {
        return [
            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
            'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
            'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
            'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
            'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins',
        ];
    }

    private function currentTenantId(): ?int
    {
        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function respondSuccess(string $message, array $data = []): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON(array_merge([
            'success'    => true,
            'message'    => $message,
            'csrf_token' => csrf_hash(),
        ], $data));
    }

    /**
     * @param array<string, mixed> $errors
     */
    private function respondError(array $errors, string $message = 'Não foi possível concluir a operação.', int $status = 422): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON([
            'success'    => false,
            'message'    => $message,
            'errors'     => $errors,
            'csrf_token' => csrf_hash(),
        ]);
    }
}
