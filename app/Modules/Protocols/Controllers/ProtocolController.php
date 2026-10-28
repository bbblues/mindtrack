<?php

namespace App\Modules\Protocols\Controllers;

use App\Controllers\BaseController;
use App\Models\BaseModel;
use App\Modules\Protocols\Models\ProtocolItemModel;
use App\Modules\Protocols\Models\ProtocolLevelModel;
use App\Modules\Protocols\Models\ProtocolModel;
use App\Modules\Protocols\Models\ProtocolSkillModel;
use App\Modules\Protocols\Models\ProtocolStatusModel;
use CodeIgniter\HTTP\RedirectResponse;

class ProtocolController extends BaseController
{
    protected ProtocolModel $protocolModel;

    protected ProtocolLevelModel $levelModel;

    protected ProtocolSkillModel $skillModel;

    protected ProtocolItemModel $itemModel;

    protected ProtocolStatusModel $statusModel;

    public function __construct()
    {
        $this->protocolModel = new ProtocolModel();
        $this->levelModel    = new ProtocolLevelModel();
        $this->skillModel    = new ProtocolSkillModel();
        $this->itemModel     = new ProtocolItemModel();
        $this->statusModel   = new ProtocolStatusModel();
    }

    /**
     * Lista os protocolos do tenant.
     */
    public function index(): string
    {
        $search    = trim((string) $this->request->getGet('search'));
        $protocols = $this->protocolModel->searchPaginated($search);
        $protocols = $this->attachSummary($protocols);

        return view('App\Modules\Protocols\Views\index', [
            'title'     => 'Protocolos',
            'protocols' => $protocols,
            'pager'     => $this->protocolModel->pager,
            'search'    => $search,
        ]);
    }

    /**
     * Exibe o formulario de criacao.
     */
    public function create(): string
    {
        return view('App\Modules\Protocols\Views\form', [
            'title'    => 'Novo Protocolo',
            'levels'   => $this->levelsForForm(),
            'statuses' => $this->statusesForForm(),
        ]);
    }

    /**
     * Persiste um novo protocolo e sua estrutura aninhada
     * (statuses e niveis -> habilidades -> itens).
     */
    public function store(): RedirectResponse
    {
        $data = $this->protocolDataFromRequest();

        $errors = [];

        if (! $this->validateData($data, $this->protocolModel->getValidationRules(), $this->protocolModel->getValidationMessages())) {
            $errors = $this->validator->getErrors();
        }

        $levelRows  = $this->request->getPost('levels') ?? [];
        $statusRows = $this->request->getPost('statuses') ?? [];

        if (! $this->hasNamedRows($levelRows)) {
            $errors[] = 'Adicione pelo menos um nivel ao protocolo.';
        }

        if (! $this->hasNamedRows($statusRows)) {
            $errors[] = 'Adicione pelo menos um status ao protocolo.';
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $tenantId = is_numeric(session('tenant_id')) ? (int) session('tenant_id') : null;
        if ($tenantId !== null) {
            $data['tenant_id'] = $tenantId;
        }

        $protocolId = $this->protocolModel->insert($data);

        if ($protocolId === false) {
            return redirect()->back()->withInput()->with('errors', $this->protocolModel->errors());
        }

        $this->saveStatuses((int) $protocolId, $tenantId, $statusRows);
        $this->saveLevels((int) $protocolId, $tenantId, $levelRows);

        return redirect()->to(site_url('protocols'))->with('success', 'Protocolo criado com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function protocolDataFromRequest(): array
    {
        $data = $this->request->getPost(['name', 'description', 'is_active']);

        $data['name']        = trim((string) ($data['name'] ?? ''));
        $data['description'] = $this->nullableText($data['description'] ?? null);
        $data['is_active']   = (int) ($data['is_active'] ?? 1);

        return $data;
    }

    /**
     * Anexa contadores (niveis, habilidades, itens e statuses) a cada protocolo.
     *
     * @param array<int, array<string, mixed>> $protocols
     *
     * @return array<int, array<string, mixed>>
     */
    private function attachSummary(array $protocols): array
    {
        if ($protocols === []) {
            return $protocols;
        }

        $ids = array_map(static fn ($protocol) => (int) $protocol['id'], $protocols);

        $levelsMap   = $this->mapCounts($this->groupedCounts($this->levelModel, 'protocol_id', $ids));
        $statusesMap = $this->mapCounts($this->groupedCounts($this->statusModel, 'protocol_id', $ids));

        $skills = $this->skillModel
            ->select('protocol_levels.protocol_id, COUNT(*) AS total')
            ->join('protocol_levels', 'protocol_levels.id = protocol_skills.level_id', 'inner')
            ->whereIn('protocol_levels.protocol_id', $ids)
            ->groupBy('protocol_levels.protocol_id')
            ->findAll();

        $items = $this->itemModel
            ->select('protocol_levels.protocol_id, COUNT(*) AS total')
            ->join('protocol_skills', 'protocol_skills.id = protocol_items.skill_id', 'inner')
            ->join('protocol_levels', 'protocol_levels.id = protocol_skills.level_id', 'inner')
            ->whereIn('protocol_levels.protocol_id', $ids)
            ->groupBy('protocol_levels.protocol_id')
            ->findAll();

        $skillsMap = $this->mapCounts($skills);
        $itemsMap  = $this->mapCounts($items);

        foreach ($protocols as &$protocol) {
            $id                         = (int) $protocol['id'];
            $protocol['levels_count']   = $levelsMap[$id] ?? 0;
            $protocol['skills_count']   = $skillsMap[$id] ?? 0;
            $protocol['items_count']    = $itemsMap[$id] ?? 0;
            $protocol['statuses_count'] = $statusesMap[$id] ?? 0;
        }
        unset($protocol);

        return $protocols;
    }

    /**
     * @param array<int, int> $ids
     *
     * @return array<int, array<string, mixed>>
     */
    private function groupedCounts(BaseModel $model, string $foreignKey, array $ids): array
    {
        return $model
            ->select($foreignKey . ', COUNT(*) AS total')
            ->whereIn($foreignKey, $ids)
            ->groupBy($foreignKey)
            ->findAll();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, int>
     */
    private function mapCounts(array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $map[(int) ($row['protocol_id'] ?? 0)] = (int) ($row['total'] ?? 0);
        }

        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $statusRows
     */
    private function saveStatuses(int $protocolId, ?int $tenantId, array $statusRows): void
    {
        foreach (array_values($statusRows) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $this->statusModel->insert([
                'tenant_id'   => $tenantId,
                'protocol_id' => $protocolId,
                'name'        => $name,
                'value'       => (int) ($row['value'] ?? 0),
                'sort_order'  => (int) ($row['sort_order'] ?? $index),
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $levelRows
     */
    private function saveLevels(int $protocolId, ?int $tenantId, array $levelRows): void
    {
        foreach (array_values($levelRows) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $levelId = $this->levelModel->insert([
                'tenant_id'   => $tenantId,
                'protocol_id' => $protocolId,
                'name'        => $name,
                'description' => $this->nullableText($row['description'] ?? null),
                'sort_order'  => (int) ($row['sort_order'] ?? $index),
            ]);

            if ($levelId === false) {
                continue;
            }

            $this->saveSkills((int) $levelId, $tenantId, $row['skills'] ?? []);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $skillRows
     */
    private function saveSkills(int $levelId, ?int $tenantId, array $skillRows): void
    {
        foreach (array_values($skillRows) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $skillId = $this->skillModel->insert([
                'tenant_id'   => $tenantId,
                'level_id'    => $levelId,
                'name'        => $name,
                'description' => $this->nullableText($row['description'] ?? null),
                'sort_order'  => (int) ($row['sort_order'] ?? $index),
            ]);

            if ($skillId === false) {
                continue;
            }

            $this->saveItems((int) $skillId, $tenantId, $row['items'] ?? []);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $itemRows
     */
    private function saveItems(int $skillId, ?int $tenantId, array $itemRows): void
    {
        foreach (array_values($itemRows) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $this->itemModel->insert([
                'tenant_id'   => $tenantId,
                'skill_id'    => $skillId,
                'name'        => $name,
                'description' => $this->nullableText($row['description'] ?? null),
                'sort_order'  => (int) ($row['sort_order'] ?? $index),
            ]);
        }
    }

    /**
     * Verifica se ao menos uma linha possui nome preenchido.
     *
     * @param array<int, mixed> $rows
     */
    private function hasNamedRows(array $rows): bool
    {
        foreach ($rows as $row) {
            if (is_array($row) && trim((string) ($row['name'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function levelsForForm(): array
    {
        $rows = old('levels');

        if (! is_array($rows)) {
            return [$this->emptyLevel()];
        }

        $levels = [];

        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $levels[] = [
                'name'        => (string) ($row['name'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'skills'      => $this->skillsForForm($row['skills'] ?? []),
            ];
        }

        return $levels !== [] ? $levels : [$this->emptyLevel()];
    }

    /**
     * @param array<int, mixed>|mixed $skills
     *
     * @return array<int, array<string, mixed>>
     */
    private function skillsForForm(mixed $skills): array
    {
        if (! is_array($skills)) {
            return [['name' => '', 'description' => '', 'items' => []]];
        }

        $result = [];

        foreach (array_values($skills) as $skill) {
            if (! is_array($skill)) {
                continue;
            }

            $result[] = [
                'name'        => (string) ($skill['name'] ?? ''),
                'description' => (string) ($skill['description'] ?? ''),
                'items'       => $this->itemsForForm($skill['items'] ?? []),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, mixed>|mixed $items
     *
     * @return array<int, array<string, mixed>>
     */
    private function itemsForForm(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $result = [];

        foreach (array_values($items) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $result[] = [
                'name'        => (string) ($item['name'] ?? ''),
                'description' => (string) ($item['description'] ?? ''),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyLevel(): array
    {
        return [
            'name'        => '',
            'description' => '',
            'skills'      => [
                [
                    'name'        => '',
                    'description' => '',
                    'items'       => [
                        ['name' => '', 'description' => ''],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function statusesForForm(): array
    {
        $rows = old('statuses');

        if (! is_array($rows)) {
            return $this->defaultStatuses();
        }

        $statuses = [];

        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $statuses[] = [
                'name'  => $name,
                'value' => (int) ($row['value'] ?? 0),
            ];
        }

        return $statuses !== [] ? $statuses : $this->defaultStatuses();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defaultStatuses(): array
    {
        return [
            ['name' => 'Não Avaliado', 'value' => 0],
            ['name' => 'Errado', 'value' => 0],
            ['name' => 'Certo', 'value' => 1],
        ];
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

