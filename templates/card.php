<?php
/**
 * Advanced search results – single result card.
 *
 * @var array          $item
 * @var SearchProvider $provider
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<article class="jankx-advanced-search__card">
    <a class="jankx-advanced-search__media" href="<?php echo esc_url($item['permalink']); ?>">
        <?php if (!empty($item['thumbnail'])) : ?>
            <img
                src="<?php echo esc_url($item['thumbnail']); ?>"
                alt="<?php echo esc_attr($item['title']); ?>"
                loading="lazy"
            />
        <?php endif; ?>
        <?php if (!empty($item['tag'])) : ?>
            <span class="jankx-advanced-search__badge"><?php echo esc_html($item['tag']); ?></span>
        <?php endif; ?>
    </a>

    <div class="jankx-advanced-search__body">
        <h2 class="jankx-advanced-search__card-title">
            <a href="<?php echo esc_url($item['permalink']); ?>"><?php echo esc_html($item['title']); ?></a>
        </h2>

        <?php if (!empty($item['excerpt'])) : ?>
            <p class="jankx-advanced-search__excerpt"><?php echo esc_html($item['excerpt']); ?></p>
        <?php endif; ?>

        <?php if ($item['rating'] > 0) : ?>
            <div class="jankx-advanced-search__rating">
                <span class="jankx-advanced-search__star" aria-hidden="true">★</span>
                <span class="jankx-advanced-search__rating-value"><?php echo esc_html($item['rating']); ?></span>
                <span class="jankx-advanced-search__rating-count">
                    (<?php echo esc_html($item['review_count']); ?> đánh giá)
                </span>
            </div>
        <?php endif; ?>

        <div class="jankx-advanced-search__footer">
            <?php if ($item['has_price']) : ?>
                <span class="jankx-advanced-search__price">
                    <?php if ($item['price_from']) : ?>
                        <span class="jankx-advanced-search__from">Từ</span>
                    <?php endif; ?>
                    <?php echo esc_html($provider->format_price($item['price'])); ?>
                </span>
            <?php else : ?>
                <span class="jankx-advanced-search__type"><?php echo esc_html($item['post_type_label']); ?></span>
            <?php endif; ?>

            <?php if (!empty($item['duration'])) : ?>
                <span class="jankx-advanced-search__duration"><?php echo esc_html($item['duration']); ?></span>
            <?php endif; ?>
        </div>
    </div>
</article>
