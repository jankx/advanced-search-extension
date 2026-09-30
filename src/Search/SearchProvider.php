<?php

namespace Jankx\Extensions\AdvancedSearch\Search;

/**
 * Central configuration for the advanced search results page.
 *
 * Nothing in this class is tied to a concrete post type. Tabs describe *what*
 * to surface through selectors, and every per-post-type detail (label, price,
 * rating, review count, duration, badge taxonomy) is derived at runtime from
 * the sources that already own that knowledge:
 *
 *  - the post type object            -> `label`
 *  - the product registry            -> price meta keys, and the price itself
 *  - the comment rating repository   -> rating / review count meta keys
 *  - the registered object taxonomies-> the card badge taxonomy
 *
 * A post type therefore becomes searchable by registering as a product — it
 * does not need to be added to this file.
 *
 * Extension points:
 *  - `jankx/advanced_search/tabs`               array    tab map
 *  - `jankx/advanced_search/tab_post_types`     array    resolved types per tab (key, types)
 *  - `jankx/advanced_search/sort_options`       array    sort key => label
 *  - `jankx/advanced_search/discover_post_types` array   searchable post types
 *  - `jankx/advanced_search/post_types`         array    post type => config
 *  - `jankx/advanced_search/post_type_config`   array    one post type's config
 *  - `jankx/advanced_search/price_meta_keys`    array    sortable price meta keys
 *  - `jankx/advanced_search/rating_meta_keys`   array    sortable rating meta keys
 *  - `jankx/advanced_search/format_price`       string   formatted price
 *  - `jankx/advanced_search/format_duration`    string   formatted duration
 *  - `jankx/advanced_search/post_tag`           string   card badge label
 */
class SearchProvider
{
    const TAB_ALL = 'all';

    const SORT_RECOMMENDED = 'recommended';
    const SORT_DATE = 'date';
    const SORT_PRICE_ASC = 'price_asc';
    const SORT_PRICE_DESC = 'price_desc';
    const SORT_RATING = 'rating';

    /**
     * Every post type entry is normalised to these keys so consumers never
     * have to guard against missing configuration.
     */
    protected static $config_defaults = [
        'label' => '',
        'price_meta' => '',
        'legacy_price_metas' => [],
        'price_from_meta' => '',
        'rating_meta' => '',
        'legacy_rating_metas' => [],
        'review_meta' => '',
        'days_meta' => '',
        'nights_meta' => '',
        'tag_taxonomies' => [],
    ];

    protected static $instance;

    /** Memoised tab map with post types already resolved. */
    protected $resolved_tabs;

    /** Memoised post type configurations. */
    protected $resolved_post_types;

    /**
     * Tab definitions.
     *
     * A tab resolves its post types from, in order:
     *  - `post_types`: an explicit list,
     *  - `products`:   every post type registered as a purchasable product,
     *  - `searchable`: every discovered post type.
     */
    protected $tabs = [
        self::TAB_ALL => [
            'label' => 'Tất cả',
            'searchable' => true,
        ],
        'guide' => [
            'label' => 'Cẩm nang du lịch',
            'post_types' => ['post'],
        ],
        'place' => [
            'label' => 'Ăn gì ở đâu',
            'post_types' => ['place'],
        ],
        'tour' => [
            'label' => 'Tour & Dịch vụ',
            'products' => true,
        ],
    ];

    protected $sort_options = [
        self::SORT_RECOMMENDED => 'Nên đặt',
        self::SORT_DATE => 'Mới nhất',
        self::SORT_PRICE_ASC => 'Giá thấp đến cao',
        self::SORT_PRICE_DESC => 'Giá cao đến thấp',
        self::SORT_RATING => 'Đánh giá cao nhất',
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

    /**
     * The tab map, with each tab's post types resolved to real post types.
     */
    public function get_tabs(): array
    {
        if ($this->resolved_tabs === null) {
            $this->resolved_tabs = [];

            foreach ($this->raw_tabs() as $key => $tab) {
                $tab = (array) $tab;
                $tab['post_types'] = $this->resolve_tab_post_types((string) $key, $tab);
                $this->resolved_tabs[$key] = $tab;
            }
        }

        return $this->resolved_tabs;
    }

    public function get_tab(string $key): array
    {
        $tabs = $this->get_tabs();

        return $tabs[$key] ?? $tabs[self::TAB_ALL] ?? [];
    }

    public function has_tab(string $key): bool
    {
        $tabs = $this->get_tabs();

        return isset($tabs[$key]);
    }

    public function normalize_tab(string $key): string
    {
        return $this->has_tab($key) ? $key : self::TAB_ALL;
    }

    public function get_tab_post_types(string $key): array
    {
        return $this->get_tab($key)['post_types'] ?? [];
    }

    /**
     * The tab map exactly as declared, without resolving post types.
     */
    protected function raw_tabs(): array
    {
        $tabs = apply_filters('jankx/advanced_search/tabs', $this->tabs);

        return is_array($tabs) ? $tabs : $this->tabs;
    }

    /**
     * Turn a tab declaration into a concrete list of post types.
     */
    protected function resolve_tab_post_types(string $key, array $tab): array
    {
        if (!empty($tab['products'])) {
            $types = $this->product_post_types();
        } elseif (!empty($tab['searchable'])) {
            $types = $this->discover_post_types();
        } else {
            $types = (array) ($tab['post_types'] ?? []);
        }

        $types = array_values(array_unique(array_filter(array_map('strval', $types))));
        $types = array_values(array_filter($types, [$this, 'is_registered_post_type']));

        return apply_filters('jankx/advanced_search/tab_post_types', $types, $key, $tab);
    }

    // ── Sorting ─────────────────────────────────────────────────────────

    public function get_sort_options(): array
    {
        $options = apply_filters('jankx/advanced_search/sort_options', $this->sort_options);

        return is_array($options) ? $options : $this->sort_options;
    }

    public function has_sort(string $key): bool
    {
        return isset($this->get_sort_options()[$key]);
    }

    public function normalize_sort(string $key): string
    {
        return $this->has_sort($key) ? $key : self::SORT_RECOMMENDED;
    }

    public function get_sort_label(string $key): string
    {
        $options = $this->get_sort_options();

        return $options[$key] ?? $options[self::SORT_RECOMMENDED] ?? '';
    }

    // ── Post type discovery ─────────────────────────────────────────────

    /**
     * Every post type the search can surface.
     *
     * Sources, in order:
     *  - post types registered as purchasable products,
     *  - post types a tab explicitly asks for,
     *  - anything added through the filter.
     */
    public function discover_post_types(): array
    {
        $types = $this->product_post_types();

        foreach ($this->raw_tabs() as $tab) {
            $declared = (array) $tab;
            $explicit = (array) ($declared['post_types'] ?? []);

            foreach ($explicit as $postType) {
                $types[] = $postType;
            }
        }

        $filtered = apply_filters('jankx/advanced_search/discover_post_types', $types);
        if (is_array($filtered)) {
            $types = $filtered;
        }

        $types = array_values(array_unique(array_filter(array_map('strval', $types))));

        return array_values(array_filter($types, [$this, 'is_registered_post_type']));
    }

    /**
     * Post types registered as purchasable products, straight from the
     * ecommerce product registry.
     */
    protected function product_post_types(): array
    {
        $registry = 'Jankx\\Extensions\\Ecommerce\\Registry\\ProductRegistry';
        if (!class_exists($registry) || !method_exists($registry, 'get_instance')) {
            return [];
        }

        $instance = $registry::get_instance();
        if (!is_object($instance) || !method_exists($instance, 'getSupportedPostTypes')) {
            return [];
        }

        return array_values(array_filter((array) $instance->getSupportedPostTypes(), 'is_string'));
    }

    protected function is_registered_post_type(string $postType): bool
    {
        if (!function_exists('post_type_exists')) {
            return true;
        }

        return (bool) post_type_exists($postType);
    }

    // ── Post type configuration ─────────────────────────────────────────

    /**
     * The full configuration for a post type, always normalised to
     * self::$config_defaults so no key ever needs a null check.
     */
    public function get_post_type_config(string $postType): array
    {
        if ($this->resolved_post_types === null) {
            $this->resolved_post_types = [];
        }

        if (!isset($this->resolved_post_types[$postType])) {
            $config = array_merge(self::$config_defaults, $this->derive_post_type_config($postType));

            $filtered = apply_filters('jankx/advanced_search/post_type_config', $config, $postType);
            if (is_array($filtered)) {
                $config = $filtered;
            }

            $this->resolved_post_types[$postType] = array_merge(self::$config_defaults, $config);
        }

        return $this->resolved_post_types[$postType];
    }

    /**
     * Build a post type's configuration from the registries that own the data.
     */
    protected function derive_post_type_config(string $postType): array
    {
        $config = ['label' => $this->post_type_label($postType)];

        $price = $this->price_meta_keys_for($postType);
        $config['price_meta'] = $price[0] ?? '';
        $config['legacy_price_metas'] = array_slice($price, 1);

        $rating = $this->rating_meta_keys_for($postType);
        $config['rating_meta'] = $rating[0] ?? '';
        $config['legacy_rating_metas'] = array_slice($rating, 1);

        $repo = $this->rating_repository();
        if ($repo !== null) {
            $config['review_meta'] = $repo::POST_COUNT_KEY;
        }

        $config['tag_taxonomies'] = $this->post_type_taxonomies($postType);

        return $config;
    }

    protected function post_type_label(string $postType): string
    {
        if ($postType === '') {
            return '';
        }

        if (!function_exists('get_post_type_object')) {
            return $postType;
        }

        $object = get_post_type_object($postType);
        if (!is_object($object)) {
            return $postType;
        }

        $label = $object->labels->name ?? '';

        return $label !== '' ? (string) $label : $postType;
    }

    /**
     * Object taxonomies attached to a post type, custom ones first so the card
     * badge prefers a domain taxonomy over the generic blog categories.
     */
    protected function post_type_taxonomies(string $postType): array
    {
        if ($postType === '' || !function_exists('get_object_taxonomies')) {
            return [];
        }

        $taxonomies = array_values((array) get_object_taxonomies($postType, 'names'));
        $taxonomies = array_values(array_filter($taxonomies, 'is_string'));

        $generic = ['category', 'post_tag', 'post_format', 'post_type'];
        usort($taxonomies, function ($a, $b) use ($generic) {
            $aGeneric = in_array($a, $generic, true) ? 1 : 0;
            $bGeneric = in_array($b, $generic, true) ? 1 : 0;

            return $aGeneric <=> $bGeneric;
        });

        return $taxonomies;
    }

    /**
     * Every meta key a post type's price may live under, primary key first.
     */
    protected function price_meta_keys_for(string $postType): array
    {
        $class = $this->product_class($postType);
        if ($class === null) {
            return [];
        }

        $keys = [];
        foreach (['PRICE_META_KEY', 'LEGACY_PRICE_META_KEYS'] as $constant) {
            if (!defined($class . '::' . $constant)) {
                continue;
            }

            $value = constant($class . '::' . $constant);
            foreach ((array) $value as $key) {
                if (is_string($key) && $key !== '') {
                    $keys[] = $key;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Every meta key a post type's rating may live under, primary key first.
     */
    protected function rating_meta_keys_for(string $postType): array
    {
        $repo = $this->rating_repository();
        if ($repo === null || !defined($repo . '::POST_AVG_KEY')) {
            return [];
        }

        $keys = [$repo::POST_AVG_KEY];

        // Legacy per-post-type display meta, so content written before the
        // rating repository existed still sorts correctly.
        $legacy = apply_filters('jankx/comment_rating/legacy_meta_map', []);
        if (is_array($legacy) && !empty($legacy[$postType])) {
            $keys[] = (string) $legacy[$postType];
        }

        return array_values(array_unique($keys));
    }

    protected function rating_repository(): ?string
    {
        $repo = 'Jankx\\Extensions\\CommentRating\\Rating\\RatingRepository';
        if (!class_exists($repo) || !defined($repo . '::POST_AVG_KEY')) {
            return null;
        }

        return $repo;
    }

    protected function product_class(string $postType): ?string
    {
        $registry = 'Jankx\\Extensions\\Ecommerce\\Registry\\ProductRegistry';
        if (!class_exists($registry) || !method_exists($registry, 'get_instance')) {
            return null;
        }

        $instance = $registry::get_instance();
        if (!is_object($instance) || !method_exists($instance, 'getProductClass')) {
            return null;
        }

        $class = $instance->getProductClass($postType);

        return is_string($class) && class_exists($class) ? $class : null;
    }

    /**
     * Instantiate the product object that owns this post's price, if any.
     */
    protected function product_for(\WP_Post $post)
    {
        $registry = 'Jankx\\Extensions\\Ecommerce\\Registry\\ProductRegistry';
        if ($post->ID <= 0 || !class_exists($registry) || !method_exists($registry, 'get_instance')) {
            return null;
        }

        $instance = $registry::get_instance();
        if (!is_object($instance) || !method_exists($instance, 'createProduct')) {
            return null;
        }

        return $instance->createProduct($post);
    }

    // ── Sortable meta keys ──────────────────────────────────────────────

    public function get_price_meta(string $postType): string
    {
        return (string) ($this->get_post_type_config($postType)['price_meta'] ?? '');
    }

    public function get_rating_meta(string $postType): string
    {
        return (string) ($this->get_post_type_config($postType)['rating_meta'] ?? '');
    }

    /**
     * Every meta key a price for the given post types may live under.
     *
     * Returning several keys is safe: SearchQuery falls back to a PHP-side
     * sort, which resolves the price through the product registry. Returning a
     * single key lets WP_Query sort in SQL.
     */
    public function price_meta_keys(array $postTypes): array
    {
        $keys = [];
        foreach ($postTypes as $postType) {
            foreach ($this->get_post_type_config((string) $postType) as $key => $value) {
                if ($key === 'price_meta' || $key === 'legacy_price_metas') {
                    foreach ((array) $value as $metaKey) {
                        if (is_string($metaKey) && $metaKey !== '') {
                            $keys[] = $metaKey;
                        }
                    }
                }
            }
        }

        $keys = array_values(array_unique($keys));

        return apply_filters('jankx/advanced_search/price_meta_keys', $keys, $postTypes);
    }

    /**
     * Every meta key a rating for the given post types may live under.
     */
    public function rating_meta_keys(array $postTypes): array
    {
        $keys = [];
        foreach ($postTypes as $postType) {
            $config = $this->get_post_type_config((string) $postType);
            foreach (['rating_meta', 'legacy_rating_metas'] as $key) {
                foreach ((array) ($config[$key] ?? []) as $metaKey) {
                    if (is_string($metaKey) && $metaKey !== '') {
                        $keys[] = $metaKey;
                    }
                }
            }
        }

        $keys = array_values(array_unique($keys));

        return apply_filters('jankx/advanced_search/rating_meta_keys', $keys, $postTypes);
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

    /**
     * The price a buyer pays: the sale price when one is set, otherwise the
     * regular price. Both are read through the product registry, so this
     * works for every post type that registers a product.
     */
    public function get_price($post): float
    {
        $post = $this->to_post($post);

        $product = $this->product_for($post);
        if (is_object($product) && method_exists($product, 'getSalePrice')) {
            $sale = (float) $product->getSalePrice();

            return $sale > 0 ? $sale : (float) $product->getPrice();
        }

        $key = $this->get_price_meta($post->post_type);

        return $key === '' ? 0.0 : (float) get_post_meta($post->ID, $key, true);
    }

    public function get_price_from($post): bool
    {
        $post = $this->to_post($post);
        $key = $this->get_post_type_config($post->post_type)['price_from_meta'];

        return $key !== '' && (bool) get_post_meta($post->ID, $key, true);
    }

    public function get_rating($post): float
    {
        $post = $this->to_post($post);
        $repo = $this->rating_repository();

        if ($repo !== null && $post->ID > 0) {
            $repository = new $repo();

            return round((float) $repository->getAverage($post->ID), 1);
        }

        $key = $this->get_rating_meta($post->post_type);

        return $key === '' ? 0.0 : round((float) get_post_meta($post->ID, $key, true), 1);
    }

    public function get_review_count($post): int
    {
        $post = $this->to_post($post);
        $key = $this->get_post_type_config($post->post_type)['review_meta'];

        return $key === '' ? 0 : (int) get_post_meta($post->ID, $key, true);
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

    public function get_excerpt(\WP_Post $post): string
    {
        $text = $post->post_excerpt;
        if ($text === '') {
            $text = $post->post_content;
        }

        return trim(wp_trim_words($text, 20, '…'));
    }

    public function get_thumbnail(\WP_Post $post): string
    {
        if (!has_post_thumbnail($post->ID)) {
            return '';
        }

        return (string) get_the_post_thumbnail_url($post->ID, 'medium_large');
    }

    /**
     * Resolve a post ID or post object to a WP_Post instance.
     */
    public function to_post($post): \WP_Post
    {
        if ($post instanceof \WP_Post) {
            return $post;
        }

        $resolved = get_post((int) $post);

        // A missing post must not pretend to be of any particular type, so the
        // empty configuration is used instead of a hardcoded fallback type.
        return $resolved instanceof \WP_Post ? $resolved : new \WP_Post(['ID' => 0, 'post_type' => '']);
    }

    // ── Formatting helpers ──────────────────────────────────────────────

    public function format_price(float $price): string
    {
        if ($price <= 0) {
            return '';
        }

        $formatted = number_format($price, 0, ',', '.') . 'đ';

        return apply_filters('jankx/advanced_search/format_price', $formatted, $price);
    }

    public function format_duration(int $days, int $nights): string
    {
        $labels = apply_filters('jankx/advanced_search/duration_labels', [
            'day' => __('ngày', 'jankx'),
            'night' => __('đêm', 'jankx'),
        ]);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' ' . ($labels['day'] ?? 'ngày');
        }
        if ($nights > 0) {
            $parts[] = $nights . ' ' . ($labels['night'] ?? 'đêm');
        }

        $formatted = implode(' ', $parts);

        return apply_filters('jankx/advanced_search/format_duration', $formatted, $days, $nights);
    }
}