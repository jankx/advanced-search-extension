<?php

/**
 * Stubs for the cross-extension classes the search provider resolves through.
 *
 * SearchProvider derives its per-post-type configuration from the product
 * registry and the rating repository rather than hardcoding meta keys, so the
 * tests need those two classes to exist. They mirror the production classes
 * (same constants, same primary-then-legacy meta resolution) so the tests
 * exercise the real derivation path.
 *
 * @package Jankx\Extensions\AdvancedSearch\Tests
 */

namespace Jankx\Extensions\AdvancedSearch\Tests\Support {

    /**
     * Mirrors Jankx\Extensions\Ecommerce\Abstracts\AbstractProduct.
     */
    abstract class AbstractProductStub
    {
        protected $id;
        protected $post;

        public function __construct($product = null)
        {
            if (is_numeric($product)) {
                $this->id = absint($product);
                $this->post = get_post($this->id);
            } elseif ($product instanceof \WP_Post) {
                $this->id = $product->ID;
                $this->post = $product;
            }
        }

        public function getId(): int
        {
            return (int) $this->id;
        }

        public function getPrice(): float
        {
            return max(0.0, (float) $this->resolveMeta(
                static::PRICE_META_KEY,
                static::LEGACY_PRICE_META_KEYS
            ));
        }

        public function getRegularPrice(): float
        {
            return max(0.0, (float) $this->resolveMeta(
                static::REGULAR_PRICE_META_KEY,
                static::LEGACY_REGULAR_PRICE_META_KEYS
            ));
        }

        public function getSalePrice(): float
        {
            return max(0.0, (float) $this->resolveMeta(
                static::SALE_PRICE_META_KEY,
                static::LEGACY_SALE_PRICE_META_KEYS
            ));
        }

        protected function resolveMeta(string $primary, array $legacy = []): string
        {
            $value = get_post_meta($this->id, $primary, true);
            if ($value !== '' && $value !== false) {
                return $value;
            }

            foreach ($legacy as $key) {
                $value = get_post_meta($this->id, $key, true);
                if ($value !== '' && $value !== false) {
                    return $value;
                }
            }

            return '';
        }
    }

    /** Mirrors Jankx\Extensions\Travel\Products\TourProduct. */
    class TourProductStub extends AbstractProductStub
    {
        const PRICE_META_KEY = '_jankx_price';
        const REGULAR_PRICE_META_KEY = '_jankx_regular_price';
        const SALE_PRICE_META_KEY = '_jankx_sale_price';
        const LEGACY_PRICE_META_KEYS = ['_tour_price'];
        const LEGACY_REGULAR_PRICE_META_KEYS = ['_tour_regular_price'];
        const LEGACY_SALE_PRICE_META_KEYS = ['_tour_sale_price'];
    }

    /** Mirrors Jankx\Extensions\Place\Products\PlaceProduct. */
    class PlaceProductStub extends AbstractProductStub
    {
        const PRICE_META_KEY = '_jankx_price';
        const REGULAR_PRICE_META_KEY = '_jankx_regular_price';
        const SALE_PRICE_META_KEY = '_jankx_sale_price';
        const LEGACY_PRICE_META_KEYS = ['_place_price'];
        const LEGACY_REGULAR_PRICE_META_KEYS = [];
        const LEGACY_SALE_PRICE_META_KEYS = [];
    }

    /** Mirrors Jankx\Extensions\Services\Products\Service. */
    class ServiceProductStub extends AbstractProductStub
    {
        const PRICE_META_KEY = '_jankx_price';
        const REGULAR_PRICE_META_KEY = '_jankx_regular_price';
        const SALE_PRICE_META_KEY = '_jankx_sale_price';
        const LEGACY_PRICE_META_KEYS = ['_service_price'];
        const LEGACY_REGULAR_PRICE_META_KEYS = ['_service_regular_price'];
        const LEGACY_SALE_PRICE_META_KEYS = ['_service_sale_price'];
    }

    /** Mirrors Jankx\Extensions\Products\Product. */
    class ShopProductStub extends AbstractProductStub
    {
        const PRICE_META_KEY = '_jankx_price';
        const REGULAR_PRICE_META_KEY = '_jankx_regular_price';
        const SALE_PRICE_META_KEY = '_jankx_sale_price';
        const LEGACY_PRICE_META_KEYS = ['_product_price'];
        const LEGACY_REGULAR_PRICE_META_KEYS = ['_product_regular_price'];
        const LEGACY_SALE_PRICE_META_KEYS = ['_product_sale_price'];
    }

    /**
     * The post types and taxonomies "registered" on this site, plus which of
     * them register a purchasable product.
     *
     * Tests describe the site through this store; the WordPress stubs in
     * bootstrap.php read from it.
     */
    class SiteRegistry
    {
        protected static $postTypes = [];
        protected static $products = [];

        public static function reset(): void
        {
            self::$postTypes = [];
            self::$products = [];
        }

        /**
         * Register a post type with a singular-ish label and its taxonomies.
         */
        public static function registerPostType(string $postType, string $label, array $taxonomies = []): void
        {
            self::$postTypes[$postType] = [
                'label' => $label,
                'taxonomies' => $taxonomies,
            ];
        }

        public static function registerProduct(
            string $postType,
            string $productClass,
            array $taxonomies = [],
            string $label = ''
        ): void {
            self::registerPostType($postType, $label !== '' ? $label : $postType, $taxonomies);
            self::$products[$postType] = $productClass;
        }

        public static function unregisterPostType(string $postType): void
        {
            unset(self::$postTypes[$postType], self::$products[$postType]);
        }

        public static function postTypeExists(string $postType): bool
        {
            return isset(self::$postTypes[$postType]);
        }

        public static function label(string $postType): ?string
        {
            return self::$postTypes[$postType]['label'] ?? null;
        }

        public static function taxonomies(string $postType): array
        {
            return self::$postTypes[$postType]['taxonomies'] ?? [];
        }

        public static function products(): array
        {
            return self::$products;
        }

        /**
         * The default site the whole suite runs against.
         */
        public static function seedDefaultSite(): void
        {
            self::registerProduct('tour', TourProductStub::class, [
                'tour_category',
                'destination',
                'tour_duration',
                'tour_tag',
            ], 'Tour');
            self::registerProduct('place', PlaceProductStub::class, ['place_type', 'destination'], 'Địa điểm');
            self::registerProduct('service', ServiceProductStub::class, ['service_category'], 'Dịch vụ');
            self::registerProduct('product', ShopProductStub::class, ['product_cat'], 'Sản phẩm');

            self::registerPostType('post', 'Cẩm nang', ['category']);
            self::registerPostType('page', 'Trang', []);
        }
    }
}

namespace Jankx\Extensions\Ecommerce\Registry {

    use Jankx\Extensions\AdvancedSearch\Tests\Support\SiteRegistry;

    /**
     * Mirrors Jankx\Extensions\Ecommerce\Registry\ProductRegistry, backed by
     * the test SiteRegistry.
     */
    class ProductRegistry
    {
        protected static $instance;

        public static function get_instance(): self
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function getSupportedPostTypes(): array
        {
            return array_keys(SiteRegistry::products());
        }

        public function getProductClass(string $postType): ?string
        {
            return SiteRegistry::products()[$postType] ?? null;
        }

        public function isSupported($post): bool
        {
            $postType = $post instanceof \WP_Post ? $post->post_type : '';

            return isset(SiteRegistry::products()[$postType]);
        }

        public function createProduct($post)
        {
            $postType = $post instanceof \WP_Post ? $post->post_type : '';
            $class = $this->getProductClass($postType);

            return $class === null ? null : new $class($post);
        }
    }
}

namespace Jankx\Extensions\CommentRating\Rating {

    /**
     * Mirrors Jankx\Extensions\CommentRating\Rating\RatingRepository for the
     * aggregate read path the search provider uses.
     */
    class RatingRepository
    {
        const COMMENT_RATING_KEY = 'jankx_comment_rating';
        const COMMENT_POST_KEY = 'jankx_comment_post_id';

        const POST_VALUES_KEY = 'jankx_rating_values';
        const POST_AVG_KEY = 'jankx_rating_average';
        const POST_COUNT_KEY = 'jankx_rating_count';

        public function getAverage(int $postId): float
        {
            return round((float) get_post_meta($postId, self::POST_AVG_KEY, true), 1);
        }

        public function getCount(int $postId): int
        {
            return (int) get_post_meta($postId, self::POST_COUNT_KEY, true);
        }
    }
}