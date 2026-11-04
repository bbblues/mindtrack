<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\SettingModel;
use CodeIgniter\HTTP\ResponseInterface;

class SettingsController extends BaseController
{
    protected SettingModel $settingModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
    }

    /**
     * Tela central de configuracoes com o grid de cards.
     */
    public function index(): string
    {
        return view('App\Modules\Core\Views\settings\index', [
            'title' => 'Configurações',
        ]);
    }

    /**
     * Tela de configuracao da Agenda (duracao padrao da sessao).
     */
    public function appointment(): string
    {
        return view('App\Modules\Core\Views\settings\appointment', [
            'title'           => 'Configurações da Agenda',
            'defaultDuration' => $this->defaultDuration(),
        ]);
    }

    /**
     * Salva a duracao padrao da sessao para a clinica logada.
     */
    public function storeAppointment(): ResponseInterface
    {
        $duration = trim((string) $this->request->getPost('duration'));

        $rules = [
            'duration' => 'required|integer|greater_than_equal_to[5]|less_than_equal_to[480]',
        ];

        $messages = [
            'duration' => [
                'required'              => 'Informe a duração padrão da sessão.',
                'integer'               => 'A duração deve ser um número inteiro de minutos.',
                'greater_than_equal_to' => 'A duração mínima é de 5 minutos.',
                'less_than_equal_to'    => 'A duração máxima é de 480 minutos (8 horas).',
            ],
        ];

        if (! $this->validateData(['duration' => $duration], $rules, $messages)) {
            return $this->respondError($this->validator->getErrors());
        }

        $config   = config('Agenda');
        $tenantId = $this->currentTenantId();

        if (! $this->settingModel->setValue((string) $config->settingKey, (string) (int) $duration, $tenantId)) {
            return $this->respondError([], 'Não foi possível salvar a configuração.');
        }

        return $this->respondSuccess('Configuração salva com sucesso.', (int) $duration);
    }

    private function defaultDuration(): int
    {
        $config   = config('Agenda');
        $tenantId = $this->currentTenantId();
        $value    = $this->settingModel->getValue((string) $config->settingKey, $tenantId);

        return is_numeric($value) && (int) $value > 0 ? (int) $value : (int) $config->defaultDuration;
    }

    private function currentTenantId(): ?int
    {
        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    private function respondSuccess(string $message, int $duration): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'success'    => true,
            'message'    => $message,
            'duration'   => $duration,
            'csrf_token' => csrf_hash(),
        ]);
    }

    /**
     * @param array<string, mixed> $errors
     */
    private function respondError(array $errors, string $message = 'Não foi possível salvar a configuração.', int $status = 422): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON([
            'success'    => false,
            'message'    => $message,
            'errors'     => $errors,
            'csrf_token' => csrf_hash(),
        ]);
    }
}
