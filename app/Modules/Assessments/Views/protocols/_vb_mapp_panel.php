<?php
/**
 * Painel de uma seção do VB-MAPP, renderizado dentro de uma aba.
 *
 * @var array<string, mixed> $level
 * @var string               $type milestones|barriers|transition|tracking|list
 */
$skillNames = [];
foreach ($level['skills'] as $skill) {
    $skillNames[] = (string) $skill['name'];
}
?>

<?php if (($level['skills'] ?? []) === []): ?>
    <p class="py-8 text-center text-sm text-gray-500">Esta seção não possui itens.</p>
<?php elseif ($type === 'milestones'): ?>
    <?php
    $cells      = [];
    $milestones = [];
    foreach ($level['skills'] as $skill) {
        foreach ($skill['items'] as $item) {
            $milestone = (int) ($item['metadata']['milestone'] ?? 0);
            if ($milestone <= 0) {
                continue;
            }
            $milestones[$milestone]                      = true;
            $cells[$milestone][$skill['name']]           = $item;
        }
    }
    ksort($milestones);
    ?>
    <div class="overflow-x-auto">
        <table class="min-w-full border-separate border-spacing-0 text-sm">
            <thead class="sticky top-0 z-10 bg-white">
                <tr>
                    <th class="sticky left-0 top-0 z-20 bg-white px-2 py-2 text-left text-xs font-semibold text-gray-500">Marco</th>
                    <?php foreach ($skillNames as $skillName): ?>
                        <th class="whitespace-nowrap px-1 py-2 text-center text-[11px] font-semibold text-gray-500"><?= esc($skillName) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php $currentGroup = null; ?>
                <?php foreach (array_keys($milestones) as $milestone): ?>
                    <?php $group = $milestone <= 5 ? 'Nível 1' : ($milestone <= 10 ? 'Nível 2' : 'Nível 3'); ?>
                    <?php if ($group !== $currentGroup): ?>
                        <?php $currentGroup = $group; ?>
                        <tr>
                            <td colspan="<?= count($skillNames) + 1 ?>" class="bg-gray-50 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-gray-500"><?= esc($group) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th class="sticky left-0 z-10 whitespace-nowrap bg-white px-2 py-1 text-left text-xs font-semibold text-gray-600"><?= (int) $milestone ?>-M</th>
                        <?php foreach ($skillNames as $skillName): ?>
                            <?php $cell = $cells[$milestone][$skillName] ?? null; ?>
                            <td class="px-1 py-1 text-center">
                                <?php if ($cell !== null && $cell['assessment_item_id'] !== null): ?>
                                    <?php $aid = (int) $cell['assessment_item_id']; ?>
                                    <button
                                        type="button"
                                        @click="toggle(<?= $aid ?>)"
                                        :disabled="finalized"
                                        :class="values[<?= $aid ?>] > 0 ? 'border-green-500 bg-green-500 text-white' : 'border-gray-300 bg-white text-transparent hover:border-green-400'"
                                        class="mx-auto flex h-8 w-8 items-center justify-center rounded-md border text-xs font-bold transition disabled:cursor-not-allowed"
                                        title="<?= esc($cell['description'] !== '' ? $cell['description'] : $cell['name']) ?>"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                <?php else: ?>
                                    <span class="mx-auto block h-8 w-8 rounded-md bg-gray-50"></span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif ($type === 'barriers' || $type === 'transition'): ?>
    <div class="space-y-2">
        <?php foreach ($level['skills'] as $skill): ?>
            <?php foreach ($skill['items'] as $item): ?>
                <?php $aid = $item['assessment_item_id'] !== null ? (int) $item['assessment_item_id'] : 0; ?>
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                    <p class="min-w-0 flex-1 text-sm font-medium text-gray-800"><?= esc($item['name']) ?></p>
                    <?php if ($aid > 0): ?>
                        <input
                            type="number"
                            min="0"
                            max="<?= (float) $item['max_score'] ?>"
                            step="1"
                            value="<?= (float) $item['score_achieved'] ?>"
                            @change="saveNumber(<?= $aid ?>, $event.target)"
                            :disabled="finalized"
                            class="w-20 rounded-lg border border-gray-300 px-3 py-2 text-center text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 disabled:bg-gray-100"
                        />
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <?php
    $cells    = [];
    $maxSteps = 0;
    foreach ($level['skills'] as $skill) {
        foreach ($skill['items'] as $item) {
            $step = (int) ($item['sort_order'] ?? 0);
            if ($step <= 0) {
                continue;
            }
            $cells[$step][$skill['name']] = $item;
            if ($step > $maxSteps) {
                $maxSteps = $step;
            }
        }
    }
    ?>
    <div class="overflow-x-auto">
        <table class="min-w-full border-separate border-spacing-0 text-sm">
            <thead class="sticky top-0 z-10 bg-white">
                <tr>
                    <th class="sticky left-0 top-0 z-20 bg-white px-2 py-2 text-left text-xs font-semibold text-gray-500">Passo</th>
                    <?php foreach ($skillNames as $skillName): ?>
                        <th class="whitespace-nowrap px-1 py-2 text-center text-[11px] font-semibold text-gray-500"><?= esc($skillName) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($step = 1; $step <= $maxSteps; $step++): ?>
                    <tr>
                        <th class="sticky left-0 z-10 whitespace-nowrap bg-white px-2 py-1 text-left text-xs font-semibold text-gray-600"><?= (int) $step ?></th>
                        <?php foreach ($skillNames as $skillName): ?>
                            <?php $cell = $cells[$step][$skillName] ?? null; ?>
                            <td class="px-1 py-1 text-center">
                                <?php if ($cell !== null && $cell['assessment_item_id'] !== null): ?>
                                    <?php
                                    $aid   = (int) $cell['assessment_item_id'];
                                    $title = trim((string) ($cell['name'] ?? ''));
                                    if (($cell['description'] ?? '') !== '') {
                                        $title = $title !== '' ? $title . ' — ' . $cell['description'] : (string) $cell['description'];
                                    }
                                    ?>
                                    <button
                                        type="button"
                                        @click="toggle(<?= $aid ?>)"
                                        :disabled="finalized"
                                        :class="values[<?= $aid ?>] > 0 ? 'border-green-500 bg-green-500 text-white' : 'border-gray-300 bg-white text-transparent hover:border-green-400'"
                                        class="mx-auto flex h-8 w-8 items-center justify-center rounded-md border text-xs font-bold transition disabled:cursor-not-allowed"
                                        title="<?= esc($title) ?>"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                <?php else: ?>
                                    <span class="mx-auto block h-8 w-8 rounded-md bg-gray-50"></span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
