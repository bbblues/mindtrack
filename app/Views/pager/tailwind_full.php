<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * @var PagerRenderer $pager
 */
$pager->setSurroundCount(2);

$linkClass   = 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium transition';
$idleClass   = $linkClass . ' border border-gray-300 bg-white text-gray-700 hover:bg-gray-50';
$activeClass = $linkClass . ' bg-blue-600 text-white';
?>

<nav aria-label="Paginação" class="flex justify-end">
    <ul class="inline-flex flex-wrap items-center gap-1">
        <?php if ($pager->hasPrevious()) : ?>
            <li>
                <a href="<?= $pager->getPrevious() ?>" aria-label="Anterior" class="<?= $idleClass ?>">&laquo;</a>
            </li>
        <?php endif; ?>

        <?php foreach ($pager->links() as $link) : ?>
            <li>
                <a href="<?= $link['uri'] ?>" class="<?= $link['active'] ? $activeClass : $idleClass ?>">
                    <?= $link['title'] ?>
                </a>
            </li>
        <?php endforeach; ?>

        <?php if ($pager->hasNext()) : ?>
            <li>
                <a href="<?= $pager->getNext() ?>" aria-label="Próximo" class="<?= $idleClass ?>">&raquo;</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
