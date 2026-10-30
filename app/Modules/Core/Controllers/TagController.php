<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\TagModel;
use CodeIgniter\HTTP\ResponseInterface;

class TagController extends BaseController
{
    protected TagModel $tagModel;

    public function __construct()
    {
        $this->tagModel = new TagModel();
    }

    /**
     * Lista as tags do tenant logado.
     */
    public function index(): string
    {
        $tags = $this->tagModel->orderBy('name', 'ASC')->findAll();

        return view('App\Modules\Core\Views\settings\tags', [
            'title'   => 'Tags',
            'tagRows' => array_map(fn (array $tag): array => $this->serializeTag($tag), $tags),
        ]);
    }

    /**
     * Cria uma tag vinculada ao tenant logado.
     */
    public function store(): ResponseInterface
    {
        $data = $this->tagDataFromRequest();

        if (! $this->validateData($data, $this->tagRules(), $this->tagMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        $tenantId = $this->currentTenantId();

        if ($this->tagModel->where('name', $data['name'])->where('tenant_id', $tenantId)->first() !== null) {
            return $this->respondError(['name' => 'Já existe uma tag com este nome na sua clínica.']);
        }

        $data['tenant_id'] = $tenantId;

        $tagId = $this->tagModel->insert($data);

        if ($tagId === false) {
            return $this->respondError($this->tagModel->errors());
        }

        $tag = $this->tagModel->find((int) $tagId);

        return $this->respondSuccess('Tag criada com sucesso.', $tag !== null ? $this->serializeTag($tag) : null);
    }

    /**
     * Atualiza uma tag existente.
     */
    public function update($id = null): ResponseInterface
    {
        $tag = $this->tagModel->find((int) $id);

        if ($tag === null) {
            return $this->respondError([], 'Tag não encontrada.', 404);
        }

        $data = $this->tagDataFromRequest();

        if (! $this->validateData($data, $this->tagRules(), $this->tagMessages())) {
            return $this->respondError($this->validator->getErrors());
        }

        $duplicate = $this->tagModel
            ->where('name', $data['name'])
            ->where('tenant_id', $this->currentTenantId())
            ->where('id !=', (int) $tag['id'])
            ->first();

        if ($duplicate !== null) {
            return $this->respondError(['name' => 'Já existe uma tag com este nome na sua clínica.']);
        }

        if (! $this->tagModel->update((int) $tag['id'], $data)) {
            return $this->respondError($this->tagModel->errors());
        }

        $updated = $this->tagModel->find((int) $tag['id']);

        return $this->respondSuccess('Tag atualizada com sucesso.', $updated !== null ? $this->serializeTag($updated) : null);
    }

    /**
     * Remove a tag (soft delete).
     */
    public function destroy($id = null): ResponseInterface
    {
        $tag = $this->tagModel->find((int) $id);

        if ($tag === null) {
            return $this->respondError([], 'Tag não encontrada.', 404);
        }

        if (! $this->tagModel->delete((int) $tag['id'])) {
            return $this->respondError($this->tagModel->errors(), 'Não foi possível excluir a tag.');
        }

        return $this->respondSuccess('Tag excluída com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function tagDataFromRequest(): array
    {
        return [
            'name'  => trim((string) $this->request->getPost('name')),
            'color' => trim((string) $this->request->getPost('color')),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function tagRules(): array
    {
        return [
            'name'  => 'required|max_length[100]',
            'color' => 'required|regex_match[/^#[0-9A-Fa-f]{6}$/]',
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function tagMessages(): array
    {
        return [
            'name' => [
                'required'   => 'Informe o nome da tag.',
                'max_length' => 'O nome da tag deve ter no máximo 100 caracteres.',
            ],
            'color' => [
                'required'    => 'Selecione uma cor para a tag.',
                'regex_match' => 'A cor deve estar no formato hexadecimal (#RRGGBB).',
            ],
        ];
    }

    private function currentTenantId(): ?int
    {
        return is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
    }

    /**
     * @param array<string, mixed> $tag
     *
     * @return array<string, mixed>
     */
    private function serializeTag(array $tag): array
    {
        return [
            'id'    => (int) $tag['id'],
            'name'  => (string) $tag['name'],
            'color' => (string) ($tag['color'] ?? '#3B82F6'),
        ];
    }

    /**
     * @param array<string, mixed>|null $tag
     */
    private function respondSuccess(string $message, ?array $tag = null): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'success'    => true,
            'message'    => $message,
            'tag'        => $tag,
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
