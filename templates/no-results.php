<?php
/**
 * Advanced search results – empty state.
 *
 * @var string $keyword
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="jankx-advanced-search__empty">
    <p class="jankx-advanced-search__empty-title">Không tìm thấy kết quả</p>
    <p class="jankx-advanced-search__empty-text">
        <?php if ($keyword !== '') : ?>
            Rất tiếc, không có kết quả nào phù hợp với &ldquo;<?php echo esc_html($keyword); ?>&rdquo;.
        <?php else : ?>
            Hãy nhập từ khóa để bắt đầu tìm kiếm.
        <?php endif; ?>
    </p>
    <p class="jankx-advanced-search__empty-hint">Hãy thử từ khóa khác hoặc chuyển sang mục khác.</p>
</div>
