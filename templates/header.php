<?php
/**
 * Advanced search results – header with keyword and search form.
 *
 * @var string $keyword
 * @var SearchResultsRenderer $renderer
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<header class="jankx-advanced-search__header">
    <h1 class="jankx-advanced-search__title">Kết quả tìm kiếm</h1>

    <?php if ($keyword !== '') : ?>
        <p class="jankx-advanced-search__subtitle">
            cho &ldquo;<?php echo esc_html($keyword); ?>&rdquo;
        </p>
    <?php endif; ?>

    <form class="jankx-advanced-search__search" role="search" method="get" action="<?php echo esc_url($renderer->url()); ?>">
        <input
            type="search"
            name="s"
            value="<?php echo esc_attr($keyword); ?>"
            placeholder="Nhập từ khóa tìm kiếm..."
            aria-label="Tìm kiếm"
        />
        <button type="submit">Tìm kiếm</button>
    </form>
</header>
