<?php
/**
 * Advanced search results – category tabs.
 *
 * @var string $keyword
 * @var string $tab
 * @var string $orderby
 * @var array  $tabs
 * @var SearchResultsRenderer $renderer
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<nav class="jankx-advanced-search__tabs" aria-label="Phân loại kết quả">
    <?php foreach ($tabs as $key => $tabConfig) : ?>
        <?php $active = ($key === $tab); ?>
        <a
            class="jankx-advanced-search__tab<?php echo $active ? ' is-active' : ''; ?>"
            href="<?php echo esc_url($renderer->url(['s' => $keyword, 'type' => $key, 'orderby' => $orderby])); ?>"
            <?php echo $active ? 'aria-current="page"' : ''; ?>
        >
            <?php echo esc_html($tabConfig['label']); ?>
        </a>
    <?php endforeach; ?>
</nav>
