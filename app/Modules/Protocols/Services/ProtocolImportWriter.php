<?php

namespace App\Modules\Protocols\Services;

use App\Modules\Protocols\Models\ProtocolItemModel;
use App\Modules\Protocols\Models\ProtocolLevelModel;
use App\Modules\Protocols\Models\ProtocolModel;
use App\Modules\Protocols\Models\ProtocolSkillModel;
use RuntimeException;

/**
 * ProtocolImportWriter
 *
 * Persiste a estrutura normalizada (payload) de um protocolo no banco usando
 * estrategia de UPSERT (achar-ou-criar/atualizar por chave natural).
 *
 * IMPORTANTE: nunca apaga registros. Isso evita que o ON DELETE CASCADE de
 * patient_assessment_items.protocol_item_id apague avaliacoes existentes.
 */
class ProtocolImportWriter
{
    protected ProtocolModel $protocolModel;

    protected ProtocolLevelModel $levelModel;

    protected ProtocolSkillModel $skillModel;

    protected ProtocolItemModel $itemModel;

    /**
     * @var array<string, int>
     */
    protected array $stats = [
        'protocols_created' => 0,
        'protocols_updated' => 0,
        'levels_created'    => 0,
        'levels_updated'    => 0,
        'skills_created'    => 0,
        'skills_updated'    => 0,
        'items_created'     => 0,
        'items_updated'     => 0,
        'items_skipped'     => 0,
    ];

    public function __construct()
    {
        $this->protocolModel = new ProtocolModel();
        $this->levelModel    = new ProtocolLevelModel();
        $this->skillModel    = new ProtocolSkillModel();
        $this->itemModel     = new ProtocolItemModel();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{protocol_id: int, stats: array<string, int>}
     */
    public function write(array $payload, bool $dryRun = false, ?int $tenantId = null): array
    {
        $this->stats = array_fill_keys(array_keys($this->stats), 0);

        $db = \Config\Database::connect();

        if (! $dryRun) {
            $db->transStart();
        }

        try {
            $protocolId = $this->upsertProtocol($payload, $dryRun, $tenantId);
            $this->upsertTree($protocolId, $payload['levels'] ?? [], $dryRun, $tenantId);
        } catch (\Throwable $e) {
            if (! $dryRun) {
                $db->transRollback();
            }

            throw $e;
        }

        if (! $dryRun) {
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('Falha na transacao de importacao.');
            }
        }

        return [
            'protocol_id' => $protocolId,
            'stats'       => $this->stats,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function upsertProtocol(array $payload, bool $dryRun, ?int $tenantId): int
    {
        $name = (string) ($payload['name'] ?? '');
        $slug = isset($payload['view_slug']) ? (string) $payload['view_slug'] : null;

        $existing = $this->protocolModel
            ->where('name', $name)
            ->where('view_slug', $slug)
            ->first();

        $data = [
            'name'        => $name,
            'description' => $this->nullableText($payload['description'] ?? null),
            'view_slug'   => $slug,
            'is_active'   => 1,
        ];

        if ($existing === null) {
            $this->stats['protocols_created']++;

            if ($dryRun) {
                return 0;
            }

            $data['tenant_id'] = $tenantId;

            $id = $this->protocolModel->insert($data);

            if ($id === false) {
                throw new RuntimeException('Falha ao criar protocolo: ' . implode(' ', $this->protocolModel->errors()));
            }

            return (int) $id;
        }

        $this->stats['protocols_updated']++;
        $id = (int) $existing['id'];

        if (! $dryRun) {
            $this->protocolModel->update($id, $data);
        }

        return $id;
    }

    /**
     * @param list<array<string, mixed>> $levels
     */
    protected function upsertTree(int $protocolId, array $levels, bool $dryRun, ?int $tenantId): void
    {
        foreach (array_values($levels) as $levelIndex => $level) {
            if (! is_array($level)) {
                continue;
            }

            $levelId = $this->upsertLevel($protocolId, $level, $levelIndex + 1, $dryRun, $tenantId);

            foreach (array_values($level['skills'] ?? []) as $skillIndex => $skill) {
                if (! is_array($skill)) {
                    continue;
                }

                $skillId = $this->upsertSkill($levelId, $skill, $skillIndex + 1, $dryRun, $tenantId);

                foreach (array_values($skill['items'] ?? []) as $itemIndex => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $this->upsertItem($skillId, $item, $itemIndex + 1, $dryRun, $tenantId);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $level
     */
    protected function upsertLevel(int $protocolId, array $level, int $sortOrder, bool $dryRun, ?int $tenantId): int
    {
        $section = trim((string) ($level['section'] ?? $level['name'] ?? ''));
        $name    = trim((string) ($level['name'] ?? $section));

        $existing = $this->levelModel
            ->where('protocol_id', $protocolId)
            ->where('section', $section)
            ->first();

        $data = [
            'name'        => $name !== '' ? $name : $section,
            'section'     => $section !== '' ? $section : null,
            'description' => $this->nullableText($level['description'] ?? null),
            'sort_order'  => (int) ($level['sort_order'] ?? $sortOrder),
        ];

        if ($existing === null) {
            $this->stats['levels_created']++;

            if ($dryRun) {
                return 0;
            }

            $data['tenant_id']   = $tenantId;
            $data['protocol_id'] = $protocolId;

            $id = $this->levelModel->insert($data);

            if ($id === false) {
                throw new RuntimeException('Falha ao criar nivel: ' . implode(' ', $this->levelModel->errors()));
            }

            return (int) $id;
        }

        $this->stats['levels_updated']++;
        $id = (int) $existing['id'];

        if (! $dryRun) {
            $this->levelModel->update($id, $data);
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $skill
     */
    protected function upsertSkill(int $levelId, array $skill, int $sortOrder, bool $dryRun, ?int $tenantId): int
    {
        $name = trim((string) ($skill['name'] ?? ''));

        if ($name === '') {
            return 0;
        }

        $existing = $this->skillModel
            ->where('level_id', $levelId)
            ->where('name', $name)
            ->first();

        $data = [
            'name'        => $name,
            'description' => $this->nullableText($skill['description'] ?? null),
            'sort_order'  => (int) ($skill['sort_order'] ?? $sortOrder),
        ];

        if ($existing === null) {
            $this->stats['skills_created']++;

            if ($dryRun) {
                return 0;
            }

            $data['tenant_id'] = $tenantId;
            $data['level_id']  = $levelId;

            $id = $this->skillModel->insert($data);

            if ($id === false) {
                throw new RuntimeException('Falha ao criar habilidade: ' . implode(' ', $this->skillModel->errors()));
            }

            return (int) $id;
        }

        $this->stats['skills_updated']++;
        $id = (int) $existing['id'];

        if (! $dryRun) {
            $this->skillModel->update($id, $data);
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function upsertItem(int $skillId, array $item, int $sortOrder, bool $dryRun, ?int $tenantId): void
    {
        $name = trim((string) ($item['name'] ?? ''));

        if ($name === '') {
            $this->stats['items_skipped']++;

            return;
        }

        if (mb_strlen($name) > 255) {
            $name = mb_substr($name, 0, 255);
        }

        $existing = $this->itemModel
            ->where('skill_id', $skillId)
            ->where('name', $name)
            ->first();

        $data = [
            'name'        => $name,
            'description' => $this->nullableText($item['description'] ?? null),
            'sort_order'  => (int) ($item['sort_order'] ?? $sortOrder),
            'max_score'   => (float) ($item['max_score'] ?? 1.0),
            'metadata'    => isset($item['metadata']) && is_array($item['metadata'])
                ? json_encode($item['metadata'], JSON_UNESCAPED_UNICODE)
                : null,
        ];

        if ($existing === null) {
            $this->stats['items_created']++;

            if ($dryRun) {
                return;
            }

            $data['tenant_id'] = $tenantId;
            $data['skill_id']  = $skillId;

            $id = $this->itemModel->insert($data);

            if ($id === false) {
                throw new RuntimeException('Falha ao criar item: ' . implode(' ', $this->itemModel->errors()));
            }

            return;
        }

        $this->stats['items_updated']++;

        if (! $dryRun) {
            $this->itemModel->update((int) $existing['id'], $data);
        }
    }

    protected function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

