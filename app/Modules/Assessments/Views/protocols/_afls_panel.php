<?php
/**
 * Painel de uma seção do AFLS, renderizado dentro de uma aba.
 *
 * @var array<string, mixed> $level
 */
$skills = $level['skills'] ?? [];
?>

<?php if ($skills === []): ?>
    <p class="py-8 text-center text-sm text-gray-500">Esta seção não possui itens.</p>
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
                                <th class="w-16 px-3 py-2">Qtde</th>
                                <th class="px-3 py-2">Tarefa</th>
                                <th class="w-28 px-3 py-2 text-center">Pontuação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <?php foreach ($skill['items'] as $item): ?>
                                <?php
                                $aid  = $item['assessment_item_id'] !== null ? (int) $item['assessment_item_id'] : 0;
                                $qtde = trim((string) ($item['metadata']['qtde'] ?? ''));

                                if ($qtde === '') {
                                    $qtde = (string) $item['name'];
                                }
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-xs font-semibold text-gray-600"><?= esc($qtde) ?></td>
                                    <td class="px-3 py-2 text-sm text-gray-800"><?= esc($item['description'] !== '' ? $item['description'] : $item['name']) ?></td>
                                    <td class="px-3 py-2 text-center">
                                        <?php if ($aid > 0): ?>
                                            <input
                                                type="number"
                                                min="0"
                                                max="<?= (float) $item['max_score'] ?>"
                                                step="1"
                                                value="<?= (float) $item['score_achieved'] ?>"
                                                @change="saveNumber(<?= $aid ?>, $event.target)"
                                                :disabled="finalized"
                                                class="mx-auto w-24 rounded-lg border border-gray-300 px-2 py-1 text-center text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 disabled:bg-gray-100"
                                            />
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
