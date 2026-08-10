<?php
/**
 * Advanced search results – pagination.
 *
 * @var string $keyword
 * @var string $tab
 * @var string $orderby
 * @var int    $page
 * @var int    $total_pages
 * @var SearchResultsRenderer $renderer
 */

if (!defined('ABSPATH')) {
    exit;
}

if ($total_pages < 2) {
    return;
}
?>
<nav class="jankx-advanced-search__pagination" aria-label="Phân trang">
    <?php if ($page > 1) : ?>
        <a
            class="jankx-advanced-search__page-link jankx-advanced-search__page-prev"
            href="<?php echo esc_url($renderer->url(['s' => $keyword, 'type' => $tab, 'orderby' => $orderby, 'paged' => $page - 1])); ?>"
        >← Trước</a>
    <?php endif; ?>

    <span class="jankx-advanced-search__page-numbers">
        <?php foreach ($renderer->pagination_items($page, $total_pages) as $link) : ?>
            <?php if ($link['type'] === 'dots') : ?>
                <span class="jankx-advanced-search__page-dots">…</span>
            <?php else : ?>
                <a
                    class="jankx-advanced-search__page-link<?php echo $link['current'] ? ' is-current' : ''; ?>"
                    href="<?php echo esc_url($renderer->url(['s' => $keyword, 'type' => $tab, 'orderby' => $orderby, 'paged' => $link['page']])); ?>"
                    <?php echo $link['current'] ? 'aria-current="page"' : ''; ?>
                ><?php echo esc_html($link['label']); ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </span>

    <?php if ($page < $total_pages) : ?>
        <a
            class="jankx-advanced-search__page-link jankx-advanced-search__page-next"
            href="<?php echo esc_url($renderer->url(['s' => $keyword, 'type' => $tab, 'orderby' => $orderby, 'paged' => $page + 1])); ?>"
        >Tiếp →</a>
    <?php endif; ?>
</nav>
