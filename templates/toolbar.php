<?php
/**
 * Advanced search results – toolbar with result count and sort control.
 *
 * @var string $keyword
 * @var string $tab
 * @var string $orderby
 * @var array  $sort_options
 * @var int    $total
 * @var SearchResultsRenderer $renderer
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="jankx-advanced-search__toolbar">
    <span class="jankx-advanced-search__count">
        <?php echo esc_html(number_format_i18n($total)); ?> kết quả
    </span>

    <form class="jankx-advanced-search__sort" method="get" action="<?php echo esc_url($renderer->url()); ?>">
        <input type="hidden" name="s" value="<?php echo esc_attr($keyword); ?>" />
        <input type="hidden" name="type" value="<?php echo esc_attr($tab); ?>" />
        <label class="jankx-advanced-search__sort-label" for="jankx-as-sort">Sắp xếp:</label>
        <select id="jankx-as-sort" name="orderby">
            <?php foreach ($sort_options as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($orderby, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
