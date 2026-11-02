<?php
/**
 * Painel de uma seção da Matriz de Comunicação, renderizado dentro de uma aba.
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
            <?php
            $items = $skill['items'] ?? [];

            if ($items === []) {
                continue;
            }

            $byCat = [];

            foreach ($items as $item) {
                $cat = (string) ($item['metadata']['categoria'] ?? 'Geral');
                $byCat[$cat][] = $item;
            }
            ?>
            <section>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <h4 class="text-sm font-semibold text-blue-700"><?= esc($skill['name']) ?></h4>
                    <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"><?= count($items) ?> itens</span>
                </div>

                <?php foreach ($byCat as $cat => $catItems): ?>
                    <div class="mb-4">
                        <h5 class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500"><?= esc($cat) ?></h5>
                        <div class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
                            <?php foreach ($catItems as $item): ?>
                                <?php $aid = $item['assessment_item_id'] !== null ? (int) $item['assessment_item_id'] : 0; ?>
                                <div class="flex items-center justify-between gap-3 px-3 py-2">
                                    <span class="text-sm text-gray-800"><?= esc($item['name']) ?></span>

                                    <?php if ($aid > 0): ?>
                                        <button
                                            type="button"
                                            @click="cycle(<?= $aid ?>)"
                                            :disabled="finalized"
                                            :class="stateClass(values[<?= $aid ?>])"
                                            class="shrink-0 rounded-lg border px-2.5 py-1 text-xs font-semibold transition disabled:cursor-not-allowed disabled:opacity-70"
                                        >
                                            <span x-text="stateLabel(values[<?= $aid ?>])"></span>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">—</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
