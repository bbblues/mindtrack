<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\NoteTemplateModel;
use CodeIgniter\HTTP\ResponseInterface;

class NoteTemplateController extends BaseController
{
    protected NoteTemplateModel $templateModel;

    public function __construct()
    {
        $this->templateModel = new NoteTemplateModel();
    }

    /**
     * Lista os modelos de nota do tenant logado.
     */
    public function index(): string
    {
        $templates = $this->templateModel->orderBy('name', 'ASC')->findAll();

        return view('App\Modules\Core\Views\settings\note_templates', [
            'title'        => 'Modelos de Nota',
            'templateRows' => array_map(fn (array $template): array => $this->serializeTemplate($template), $templates),
        ]);
    }

    /**
     * Cria um modelo de nota vinculado ao tenant logado.
     */
    public function store(): ResponseInterface
    {
        $data = $this->templateDataFromRequest();

        if (! $this->validateData($data, $this->templateRules(), $this->templateMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        $tenantId = $this->currentTenantId();

        if ($this->templateModel->where('name', $data['name'])->where('tenant_id', $tenantId)->first() !== null) {
            return $this->respondError(['name' => 'Já existe um modelo de nota com este nome na sua clínica.']);
        }

        $data['tenant_id'] = $tenantId;

        $templateId = $this->templateModel->insert($data);

        if ($templateId === false) {
            return $this->respondError($this->templateModel->errors());
        }

        $template = $this->templateModel->find((int) $templateId);

        return $this->respondSuccess('Modelo de nota criado com sucesso.', $template !== null ? $this->serializeTemplate($template) : null);
    }

    /**
     * Atualiza um modelo de nota existente.
     */
    public function update($id = null): ResponseInterface
    {
        $template = $this->templateModel->find((int) $id);

        if ($template === null) {
            return $this->respondError([], 'Modelo de nota não encontrado.', 404);
        }

        $data = $this->templateDataFromRequest();

        if (! $this->validateData($data, $this->templateRules(), $this->templateMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        $duplicate = $this->templateModel
            ->where('name', $data['name'])
            ->where('tenant_id', $this->currentTenantId())
            ->where('id !=', (int) $template['id'])
            ->first();

        if ($duplicate !== null) {
            return $this->respondError(['name' => 'Já existe um modelo de nota com este nome na sua clínica.']);
        }

        if (! $this->templateModel->update((int) $template['id'], $data)) {
            return $this->respondError($this->templateModel->errors());
        }

        $updated = $this->templateModel->find((int) $template['id']);

        return $this->respondSuccess('Modelo de nota atualizado com sucesso.', $updated !== null ? $this->serializeTemplate($updated) : null);
    }

    /**
     * Remove o modelo de nota (soft delete).
     */
    public function destroy($id = null): ResponseInterface
    {
        $template = $this->templateModel->find((int) $id);

        if ($template === null) {
            return $this->respondError([], 'Modelo de nota não encontrado.', 404);
        }

        if (! $this->templateModel->delete((int) $template['id'])) {
            return $this->respondError($this->templateModel->errors(), 'Não foi possível excluir o modelo de nota.');
        }

        return $this->respondSuccess('Modelo de nota excluído com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function templateDataFromRequest(): array
    {
        return [
            'name'    => trim((string) $this->request->getPost('name')),
            'content' => trim((string) $this->request->getPost('content')),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function templateRules(): array
    {
        return [
            'name'    => 'required|max_length[255]',
            'content' => 'required',
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function templateMessages(): array
    {
        return [
            'name' => [
                'required'   => 'Informe o nome do modelo.',
                'max_length' => 'O nome do modelo deve ter no máximo 255 caracteres.',
            ],
            'content' => [
                'required' => 'Informe o texto padrão do modelo.',
            ],
        ];
    }

    private function currentTenantId(): ?int
    {
        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    /**
     * @param array<string, mixed> $template
     *
     * @return array<string, mixed>
     */
    private function serializeTemplate(array $template): array
    {
        $content = (string) ($template['content'] ?? '');

        return [
            'id'      => (int) $template['id'],
            'name'    => (string) $template['name'],
            'content' => $content,
            'preview' => $this->preview($content),
        ];
    }

    private function preview(string $content, int $length = 90): string
    {
        $content = (string) preg_replace('/\s+/', ' ', trim($content));

        if ($content === '' || mb_strlen($content) <= $length) {
            return $content;
        }

        return mb_substr($content, 0, $length) . '…';
    }

    /**
     * @param array<string, mixed>|null $template
     */
    private function respondSuccess(string $message, ?array $template = null): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'success'    => true,
            'message'    => $message,
            'template'   => $template,
            'csrf_token' => csrf_hash(),
        ]);
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
