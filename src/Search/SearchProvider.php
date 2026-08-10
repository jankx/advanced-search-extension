<?php

namespace Jankx\Extensions\AdvancedSearch\Search;

/**
 * Central configuration for the advanced search results page.
 *
 * Defines:
 *  - the result tabs (Tất cả / Trải nghiệm / Cẩm nang du lịch /
 *    Ăn gì ở đâu / Tour & Dịch vụ) and the post types each covers,
 *  - the sort options exposed in the toolbar,
 *  - per-post-type meta keys used to render price, rating, review count
 *    and duration, plus the taxonomy used for the card badge,
 *  - helpers to normalise request values and format card data.
 */
class SearchProvider
{
    const TAB_ALL = 'all';
    const TAB_EXPERIENCE = 'experience';
    const TAB_GUIDE = 'guide';
    const TAB_PLACE = 'place';
    const TAB_TOUR = 'tour';

    const SORT_RECOMMENDED = 'recommended';
    const SORT_DATE = 'date';
    const SORT_PRICE_ASC = 'price_asc';
    const SORT_PRICE_DESC = 'price_desc';
    const SORT_RATING = 'rating';

    protected static $instance;

    protected $tabs = [
        self::TAB_ALL => [
            'label' => 'Tất cả',
            'post_types' => ['tour', 'experience', 'place', 'product', 'post'],
        ],
        self::TAB_EXPERIENCE => [
            'label' => 'Trải nghiệm',
            'post_types' => ['experience'],
        ],
        self::TAB_GUIDE => [
            'label' => 'Cẩm nang du lịch',
            'post_types' => ['post'],
        ],
        self::TAB_PLACE => [
            'label' => 'Ăn gì ở đâu',
            'post_types' => ['place'],
        ],
        self::TAB_TOUR => [
            'label' => 'Tour & Dịch vụ',
            'post_types' => ['tour', 'product'],
        ],
    ];

    protected $sort_options = [
        self::SORT_RECOMMENDED => 'Nên đặt',
        self::SORT_DATE => 'Mới nhất',
        self::SORT_PRICE_ASC => 'Giá thấp đến cao',
        self::SORT_PRICE_DESC => 'Giá cao đến thấp',
        self::SORT_RATING => 'Đánh giá cao nhất',
    ];

    protected $post_types = [
        'tour' => [
            'label' => 'Tour',
            'price_meta' => '_tour_price',
            'price_from_meta' => '_tour_price_is_from',
            'rating_meta' => '_tour_rating',
            'review_meta' => '_tour_review_count',
            'days_meta' => '_tour_duration_days',
            'nights_meta' => '_tour_duration_nights',
            'tag_taxonomies' => ['tour_category', 'destination'],
        ],
        'experience' => [
            'label' => 'Trải nghiệm',
            'price_meta' => '_experience_price',
            'price_from_meta' => '',
            'rating_meta' => '_experience_rating',
            'review_meta' => '_experience_review_count',
            'days_meta' => '',
            'nights_meta' => '',
            'tag_taxonomies' => ['destination'],
        ],
        'place' => [
            'label' => 'Địa điểm',
            'price_meta' => '_place_price',
            'price_from_meta' => '',
            'rating_meta' => '_place_rating',
            'review_meta' => '_place_review_count',
            'days_meta' => '',
            'nights_meta' => '',
            'tag_taxonomies' => ['place_type', 'destination'],
        ],
        'product' => [
            'label' => 'Sản phẩm',
            'price_meta' => '_product_price',
            'price_from_meta' => '',
            'rating_meta' => '',
            'review_meta' => '',
            'days_meta' => '',
            'nights_meta' => '',
            'tag_taxonomies' => [],
        ],
        'post' => [
            'label' => 'Cẩm nang',
            'price_meta' => '',
            'price_from_meta' => '',
            'rating_meta' => '',
            'review_meta' => '',
            'days_meta' => '',
            'nights_meta' => '',
            'tag_taxonomies' => ['category'],
        ],
    ];

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function reset_instance(): void
    {
        self::$instance = null;
    }

    // ── Tabs ────────────────────────────────────────────────────────────

    public function get_tabs(): array
    {
        return $this->tabs;
    }

    public function get_tab(string $key): array
    {
        return $this->tabs[$key] ?? $this->tabs[self::TAB_ALL];
    }

    public function has_tab(string $key): bool
    {
        return isset($this->tabs[$key]);
    }

    public function normalize_tab(string $key): string
    {
        return $this->has_tab($key) ? $key : self::TAB_ALL;
    }

    public function get_tab_post_types(string $key): array
    {
        return $this->tabs[$key]['post_types'] ?? [];
    }

    // ── Sorting ─────────────────────────────────────────────────────────

    public function get_sort_options(): array
    {
        return $this->sort_options;
    }

    public function has_sort(string $key): bool
    {
        return isset($this->sort_options[$key]);
    }

    public function normalize_sort(string $key): string
    {
        return $this->has_sort($key) ? $key : self::SORT_RECOMMENDED;
    }

    public function get_sort_label(string $key): string
    {
        return $this->sort_options[$key] ?? $this->sort_options[self::SORT_RECOMMENDED];
    }

    // ── Post type configuration ─────────────────────────────────────────

    public function get_post_type_config(string $postType): array
    {
        return $this->post_types[$postType] ?? [
            'label' => $postType,
            'price_meta' => '',
            'price_from_meta' => '',
            'rating_meta' => '',
            'review_meta' => '',
            'days_meta' => '',
            'nights_meta' => '',
            'tag_taxonomies' => [],
        ];
    }

    public function get_price_meta(string $postType): string
    {
        return (string) ($this->get_post_type_config($postType)['price_meta'] ?? '');
    }

    public function get_rating_meta(string $postType): string
    {
        return (string) ($this->get_post_type_config($postType)['rating_meta'] ?? '');
    }

    /**
     * Unique price meta keys across the given post types.
     *
     * Returns an empty array when no post type has a price, a single-element
     * array when every type shares one key (safe for a SQL meta_value_num
     * sort), or several keys (which forces a PHP-side sort).
     */
    public function price_meta_keys(array $postTypes): array
    {
        $keys = [];
        foreach ($postTypes as $postType) {
            $key = $this->get_price_meta($postType);
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    public function rating_meta_keys(array $postTypes): array
    {
        $keys = [];
        foreach ($postTypes as $postType) {
            $key = $this->get_rating_meta($postType);
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    // ── Card data ───────────────────────────────────────────────────────

    public function format_post($post): array
    {
        $post = $this->to_post($post);
        $config = $this->get_post_type_config($post->post_type);
        $price = $this->get_price($post);
        $rating = $this->get_rating($post);
        $duration = $this->get_duration($post);

        return [
            'id' => (int) $post->ID,
            'post_type' => $post->post_type,
            'post_type_label' => $config['label'],
            'title' => get_the_title($post),
            'permalink' => get_permalink($post),
            'excerpt' => $this->get_excerpt($post),
            'thumbnail' => $this->get_thumbnail($post),
            'tag' => $this->get_tag($post),
            'rating' => $rating,
            'review_count' => $this->get_review_count($post),
            'price' => $price,
            'price_from' => $this->get_price_from($post),
            'has_price' => $price > 0,
            'duration' => $duration,
            'has_duration' => $duration !== '',
        ];
    }

    public function get_price($post): float
    {
        $post = $this->to_post($post);
        $config = $this->get_post_type_config($post->post_type);

        if ($post->post_type === 'product' && $config['price_meta'] !== '') {
            $price = (float) get_post_meta($post->ID, $config['price_meta'], true);
            $sale = (float) get_post_meta($post->ID, '_product_sale_price', true);

            return $sale > 0 ? $sale : $price;
        }

        if ($config['price_meta'] === '') {
            return 0.0;
        }

        return (float) get_post_meta($post->ID, $config['price_meta'], true);
    }

    public function get_price_from($post): bool
    {
        $post = $this->to_post($post);
        $config = $this->get_post_type_config($post->post_type);
        if ($config['price_from_meta'] === '') {
            return false;
        }

        return (bool) get_post_meta($post->ID, $config['price_from_meta'], true);
    }

    public function get_rating($post): float
    {
        $post = $this->to_post($post);
        $config = $this->get_post_type_config($post->post_type);
        if ($config['rating_meta'] === '') {
            return 0.0;
        }

        return round((float) get_post_meta($post->ID, $config['rating_meta'], true), 1);
    }

    public function get_review_count($post): int
    {
        $post = $this->to_post($post);
        $config = $this->get_post_type_config($post->post_type);
        if ($config['review_meta'] === '') {
            return 0;
        }

        return (int) get_post_meta($post->ID, $config['review_meta'], true);
    }

    public function get_duration($post): string
    {
        $post = $this->to_post($post);
        $config = $this->get_post_type_config($post->post_type);

        $days = $config['days_meta'] !== '' ? (int) get_post_meta($post->ID, $config['days_meta'], true) : 0;
        $nights = $config['nights_meta'] !== '' ? (int) get_post_meta($post->ID, $config['nights_meta'], true) : 0;

        return $this->format_duration($days, $nights);
    }

    public function get_tag($post): string
    {
        $post = $this->to_post($post);
        $config = $this->get_post_type_config($post->post_type);

        foreach ($config['tag_taxonomies'] as $taxonomy) {
            $terms = get_the_terms($post->ID, $taxonomy);
            if ($terms && !is_wp_error($terms) && !empty($terms)) {
                return (string) $terms[0]->name;
            }
        }

        return (string) $config['label'];
    }

    protected function get_excerpt(\WP_Post $post): string
    {
        $text = $post->post_excerpt;
        if ($text === '') {
            $text = $post->post_content;
        }

        return trim(wp_trim_words($text, 20, '…'));
    }

    protected function get_thumbnail(\WP_Post $post): string
    {
        if (!has_post_thumbnail($post->ID)) {
            return '';
        }

        return (string) get_the_post_thumbnail_url($post->ID, 'medium_large');
    }

    /**
     * Resolve a post ID or post object to a WP_Post instance.
     */
    protected function to_post($post): \WP_Post
    {
        if ($post instanceof \WP_Post) {
            return $post;
        }

        $resolved = get_post((int) $post);

        return $resolved instanceof \WP_Post ? $resolved : new \WP_Post(['ID' => 0, 'post_type' => 'post']);
    }

    // ── Formatting helpers ──────────────────────────────────────────────

    public function format_price(float $price): string
    {
        if ($price <= 0) {
            return '';
        }

        return number_format($price, 0, ',', '.') . 'đ';
    }

    public function format_duration(int $days, int $nights): string
    {
        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' ngày';
        }
        if ($nights > 0) {
            $parts[] = $nights . ' đêm';
        }

        return implode(' ', $parts);
    }
}
