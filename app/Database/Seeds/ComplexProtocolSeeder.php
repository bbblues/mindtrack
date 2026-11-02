<?php

namespace App\Database\Seeds;

use App\Modules\Protocols\Models\ProtocolItemModel;
use App\Modules\Protocols\Models\ProtocolLevelModel;
use App\Modules\Protocols\Models\ProtocolModel;
use App\Modules\Protocols\Models\ProtocolSkillModel;
use App\Modules\Protocols\Models\ProtocolStatusModel;
use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * ComplexProtocolSeeder
 *
 * Importa protocolos complexos (VB-MAPP, AFLS, ABLLS-R) a partir de arquivos
 * JSON armazenados em database/data/.
 */
class ComplexProtocolSeeder extends Seeder
{
    public function run()
    {
        $files = glob(ROOTPATH . 'database/data/*.json');

        if ($files === false || $files === []) {
            echo 'Nenhum JSON encontrado em database/data.' . PHP_EOL;

            return;
        }

        foreach ($files as $file) {
            $this->importFile($file);
        }
    }

    private function importFile(string $file): void
    {
        $json = file_get_contents($file);
        $data = json_decode((string) $json, true);

        if (! is_array($data) || empty($data['name'])) {
            throw new RuntimeException('JSON invalido ou sem "name": ' . $file);
        }

        $name     = (string) $data['name'];
        $viewSlug = isset($data['view_slug']) ? (string) $data['view_slug'] : null;

        $protocolModel = new ProtocolModel();

        $existing = $protocolModel
            ->where('name', $name)
            ->where('view_slug', $viewSlug)
            ->first();

        if ($existing !== null) {
            $protocolId = (int) $existing['id'];
            $tenantId   = $existing['tenant_id'] !== null ? (int) $existing['tenant_id'] : null;

            $this->clearLevels($protocolId);

            echo "Protocolo '{$name}' ja existe. Niveis/habilidades/itens substituidos." . PHP_EOL;
        } else {
            $tenantId = $this->tenantId();

            $protocolId = $protocolModel->insert([
                'tenant_id'   => $tenantId,
                'name'        => $name,
                'description' => $this->nullableText($data['description'] ?? null),
                'view_slug'   => $viewSlug,
                'is_active'   => 1,
            ]);

            if ($protocolId === false) {
                throw new RuntimeException('Falha ao criar o protocolo: ' . implode(' ', $protocolModel->errors()));
            }

            $protocolId = (int) $protocolId;

            $this->importStatuses($protocolId, $tenantId, $data['statuses'] ?? null);
        }

        $this->importLevels($protocolId, $tenantId, $data['levels'] ?? []);

        echo "Protocolo '{$name}' importado com sucesso." . PHP_EOL;
    }

    /**
     * Remove fisicamente os niveis do protocolo (hard delete).
     *
     * As FKs de protocol_skills.level_id e protocol_items.skill_id usam
     * ON DELETE CASCADE, portanto habilidades e itens sao removidos em cascata.
     */
    private function clearLevels(int $protocolId): void
    {
        $levelModel = new ProtocolLevelModel();

        $levelModel
            ->builder()
            ->where('protocol_id', $protocolId)
            ->delete();
    }

    /**
     * @param array<int, mixed>|null $statuses
     */
    private function importStatuses(int $protocolId, ?int $tenantId, ?array $statuses): void
    {
        if (! is_array($statuses) || $statuses === []) {
            $statuses = [
                ['name' => 'Não Avaliado', 'value' => 0, 'sort_order' => 1],
                ['name' => 'Errado', 'value' => 0, 'sort_order' => 2],
                ['name' => 'Certo', 'value' => 1, 'sort_order' => 3],
            ];
        }

        $model = new ProtocolStatusModel();

        foreach (array_values($statuses) as $index => $status) {
            if (! is_array($status) || empty($status['name'])) {
                continue;
            }

            $inserted = $model->insert([
                'tenant_id'   => $tenantId,
                'protocol_id' => $protocolId,
                'name'        => (string) $status['name'],
                'value'       => (int) ($status['value'] ?? 0),
                'sort_order'  => (int) ($status['sort_order'] ?? ($index + 1)),
            ]);

            if ($inserted === false) {
                throw new RuntimeException('Falha ao criar o status: ' . implode(' ', $model->errors()));
            }
        }
    }

    /**
     * @param array<int, mixed> $levels
     */
    private function importLevels(int $protocolId, ?int $tenantId, array $levels): void
    {
        $levelModel = new ProtocolLevelModel();
        $skillModel = new ProtocolSkillModel();
        $itemModel  = new ProtocolItemModel();

        foreach (array_values($levels) as $levelIndex => $level) {
            if (! is_array($level) || empty($level['name'])) {
                continue;
            }

            $levelId = $levelModel->insert([
                'tenant_id'   => $tenantId,
                'protocol_id' => $protocolId,
                'name'        => (string) $level['name'],
                'section'     => isset($level['section']) && trim((string) $level['section']) !== ''
                    ? (string) $level['section']
                    : 'Geral',
                'description' => $this->nullableText($level['description'] ?? null),
                'sort_order'  => (int) ($level['sort_order'] ?? ($levelIndex + 1)),
            ]);

            if ($levelId === false) {
                throw new RuntimeException('Falha ao criar o nivel: ' . implode(' ', $levelModel->errors()));
            }

            $levelId = (int) $levelId;

            foreach (array_values($level['skills'] ?? []) as $skillIndex => $skill) {
                if (! is_array($skill) || empty($skill['name'])) {
                    continue;
                }

                $skillId = $skillModel->insert([
                    'tenant_id'   => $tenantId,
                    'level_id'    => $levelId,
                    'name'        => (string) $skill['name'],
                    'description' => $this->nullableText($skill['description'] ?? null),
                    'sort_order'  => (int) ($skill['sort_order'] ?? ($skillIndex + 1)),
                ]);

                if ($skillId === false) {
                    throw new RuntimeException('Falha ao criar a habilidade: ' . implode(' ', $skillModel->errors()));
                }

                $skillId = (int) $skillId;

                foreach (array_values($skill['items'] ?? []) as $itemIndex => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $code        = trim((string) ($item['code'] ?? ''));
                    $description = $this->nullableText($item['description'] ?? null);
                    $name        = $code !== '' ? $code : mb_substr((string) ($item['description'] ?? 'Item ' . ($itemIndex + 1)), 0, 255);

                    $inserted = $itemModel->insert([
                        'tenant_id'   => $tenantId,
                        'skill_id'    => $skillId,
                        'name'        => $name,
                        'description' => $description,
                        'sort_order'  => (int) ($item['sort_order'] ?? ($itemIndex + 1)),
                        'max_score'   => (float) ($item['max_score'] ?? 1.0),
                        'metadata'    => isset($item['metadata']) && is_array($item['metadata'])
                            ? json_encode($item['metadata'])
                            : null,
                    ]);

                    if ($inserted === false) {
                        throw new RuntimeException('Falha ao criar o item: ' . implode(' ', $itemModel->errors()));
                    }
                }
            }
        }
    }

    private function tenantId(): ?int
    {
        foreach ($_SERVER['argv'] ?? [] as $arg) {
            if (str_starts_with((string) $arg, '--tenant=')) {
                return (int) substr((string) $arg, strlen('--tenant='));
            }
        }

        return null;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

