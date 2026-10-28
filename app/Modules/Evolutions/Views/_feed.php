<?php

$formatFeedDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp === false ? $value : date('d/m/Y H:i', $timestamp);
};

$feedEmptyTitle   = $emptyTitle ?? 'Nenhuma evolução registrada';
$feedEmptyText    = $emptyText ?? 'As evoluções das sessões aparecerão aqui conforme forem registradas.';
$feedEmptyCtaUrl  = $emptyCtaUrl ?? site_url('agenda');
$feedEmptyCtaLabel = $emptyCtaLabel ?? 'Ir para a Agenda';
?>

<?php if (empty($evolutions)): ?>
    <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        <p class="text-sm font-medium text-gray-700"><?= esc($feedEmptyTitle) ?></p>
        <p class="mt-1 text-sm text-gray-500"><?= esc($feedEmptyText) ?></p>
        <a href="<?= esc($feedEmptyCtaUrl) ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
            <?= esc($feedEmptyCtaLabel) ?>
        </a>
    </div>
<?php else: ?>
    <ol class="relative space-y-6 border-l border-gray-200 pl-6">
        <?php foreach ($evolutions as $evolution): ?>
            <li class="relative">
                <span class="absolute -left-[31px] top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 ring-4 ring-white"></span>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-800"><?= esc($evolution['professional_name'] ?? 'Profissional') ?></p>
                        <p class="text-xs text-gray-500">Sessão em <?= esc($formatFeedDate($evolution['appointment_start_time'] ?? null)) ?></p>
                    </div>
                    <span class="text-xs text-gray-400"><?= esc($formatFeedDate($evolution['created_at'] ?? null)) ?></span>
                </div>

                <p class="mt-2 whitespace-pre-line text-sm text-gray-700"><?= esc($evolution['evolution_text'] ?? '') ?></p>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
