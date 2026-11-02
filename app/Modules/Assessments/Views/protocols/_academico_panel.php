<?php
/**
 * Painel de uma área da Avaliação Global Academia do Autismo.
 *
 * @var array<string, mixed> $level
 */
$skills = $level['skills'] ?? [];
?>
<?php if ($skills === []): ?>
    <p class="py-8 text-center text-sm text-gray-500">Esta área não possui itens.</p>
<?php else: ?>
    <div class="space-y-6">
        <?php foreach ($skills as $skill): ?>
            <?php if (($skill['items'] ?? []) === []) { continue; } ?>
            <section>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <h4 class="text-sm font-semibold text-blue-700"><?= esc($skill['name']) ?></h4>
                    <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"><?= count($skill['items']) ?> itens</span>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="w-14 px-3 py-2">#</th>
                                <th class="px-3 py-2">Tarefa</th>
                                <th class="w-40 px-3 py-2 text-center">Pontuação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <?php foreach ($skill['items'] as $item): ?>
                                <?php $aid = $item['assessment_item_id'] !== null ? (int) $item['assessment_item_id'] : 0; ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-xs font-semibold text-gray-600"><?= esc($item['name']) ?></td>
                                    <td class="px-3 py-2 text-sm text-gray-800"><?= esc($item['description'] !== '' ? $item['description'] : $item['name']) ?></td>
                                    <td class="px-3 py-2 text-center">
                                        <?php if ($aid > 0): ?>
                                            <button
                                                type="button"
                                                @click="cycle(<?= $aid ?>)"
                                                :disabled="finalized"
                                                :class="stateClass(values[<?= $aid ?>])"
                                                class="mx-auto rounded-lg border px-3 py-1 text-xs font-semibold transition disabled:cursor-not-allowed disabled:opacity-70"
                                            >
                                                <span x-text="stateLabel(values[<?= $aid ?>])"></span>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
