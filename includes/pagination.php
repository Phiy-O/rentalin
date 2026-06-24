<?php

function render_pagination($total, $perPage, $page, $baseUrl) {
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min((int) $page, $totalPages));
    if ($totalPages <= 1) return '';

    $buildUrl = function ($p) use ($baseUrl) {
        $connector = (strpos($baseUrl, '?') !== false) ? '&' : '?';
        return $baseUrl . $connector . 'page=' . $p;
    };

    ob_start();
    ?>
    <div class="pagination">
        <div class="pagination-info">
            <?= $total; ?> hasil — halaman <?= $page; ?> dari <?= $totalPages; ?>
        </div>
        <div class="pagination-links">
            <?php if ($page > 1): ?>
                <a href="<?= $buildUrl($page - 1); ?>" class="pagination-link pagination-prev"><?php render_icon('chevron-left'); ?> Sebelumnya</a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            if ($start > 1): ?>
                <a href="<?= $buildUrl(1); ?>" class="pagination-link">1</a>
                <?php if ($start > 2): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
            <?php endif;

            for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="pagination-link pagination-active"><?= $i; ?></span>
                <?php else: ?>
                    <a href="<?= $buildUrl($i); ?>" class="pagination-link"><?= $i; ?></a>
                <?php endif; ?>
            <?php endfor;

            if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
                <a href="<?= $buildUrl($totalPages); ?>" class="pagination-link"><?= $totalPages; ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= $buildUrl($page + 1); ?>" class="pagination-link pagination-next">Berikutnya <?php render_icon('chevron-right'); ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
