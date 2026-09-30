<?php

namespace Jankx\Extensions\AdvancedSearch\Tests\Search;

use Jankx\Extensions\AdvancedSearch\Search\SearchProvider;
use Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore;
use Jankx\Extensions\AdvancedSearch\Tests\Support\ServiceProductStub;
use Jankx\Extensions\AdvancedSearch\Tests\Support\SiteRegistry;
use Jankx\Extensions\AdvancedSearch\Tests\TestCase;

/**
 * @coversDefaultClass \Jankx\Extensions\AdvancedSearch\Search\SearchProvider
 */
class SearchProviderTest extends TestCase
{
    public function test_tabs_expose_a_label_and_resolved_post_types()
    {
        $tabs = $this->provider()->get_tabs();

        $this->assertSame(['all', 'guide', 'place', 'tour'], array_keys($tabs));
        $this->assertSame('Tất cả', $tabs['all']['label']);
        $this->assertSame('Cẩm nang du lịch', $tabs['guide']['label']);
        $this->assertSame('Ăn gì ở đâu', $tabs['place']['label']);
        $this->assertSame('Tour & Dịch vụ', $tabs['tour']['label']);

        foreach ($tabs as $key => $tab) {
            $this->assertIsArray($tab['post_types'], "Tab {$key} must resolve post types");
        }
    }

    /**
     * The "products" selector takes every post type registered as a product,
     * so `service` shows up without being written down anywhere.
     */
    public function test_product_tab_covers_every_registered_product()
    {
        $this->assertSame(
            ['tour', 'place', 'service', 'product'],
            $this->provider()->get_tab_post_types('tour')
        );
    }

    public function test_all_tab_covers_every_discovered_post_type()
    {
        $this->assertSame(
            ['tour', 'place', 'service', 'product', 'post'],
            $this->provider()->get_tab_post_types(SearchProvider::TAB_ALL)
        );
    }

    public function test_a_new_product_post_type_becomes_searchable_without_code_changes()
    {
        SiteRegistry::registerProduct('cruise', ServiceProductStub::class, ['cruise_region'], 'Cruise');

        $provider = $this->provider();

        $this->assertContains('cruise', $provider->get_tab_post_types(SearchProvider::TAB_ALL));
        $this->assertContains('cruise', $provider->get_tab_post_types('tour'));
        $this->assertSame('Cruise', $provider->get_post_type_config('cruise')['label']);
    }

    public function test_unregistered_post_types_are_dropped()
    {
        SiteRegistry::unregisterPostType('place');

        $this->assertNotContains(
            'place',
            $this->provider()->get_tab_post_types(SearchProvider::TAB_ALL)
        );
    }

    public function test_tabs_can_be_added_through_a_filter()
    {
        add_filter('jankx/advanced_search/tabs', function ($tabs) {
            $tabs['cruise'] = [
                'label' => 'Du thuyền',
                'post_types' => ['cruise'],
            ];

            return $tabs;
        });

        SiteRegistry::registerPostType('cruise', 'Cruise', []);

        $tabs = $this->provider()->get_tabs();

        $this->assertArrayHasKey('cruise', $tabs);
        $this->assertSame('Du thuyền', $tabs['cruise']['label']);
        $this->assertSame(['cruise'], $tabs['cruise']['post_types']);
    }

    public function test_tab_post_types_filter_can_narrow_a_tab()
    {
        add_filter('jankx/advanced_search/tab_post_types', function ($types, $key) {
            return $key === 'tour' ? ['tour'] : $types;
        }, 10, 2);

        $this->assertSame(['tour'], $this->provider()->get_tab_post_types('tour'));
    }

    public function test_normalize_tab_falls_back_to_all()
    {
        $provider = $this->provider();

        $this->assertSame('all', $provider->normalize_tab('all'));
        $this->assertSame('place', $provider->normalize_tab('place'));
        $this->assertSame('all', $provider->normalize_tab('unknown'));
        $this->assertSame('all', $provider->normalize_tab(''));
    }

    public function test_normalize_sort_falls_back_to_recommended()
    {
        $provider = $this->provider();

        $this->assertSame('recommended', $provider->normalize_sort('recommended'));
        $this->assertSame('price_asc', $provider->normalize_sort('price_asc'));
        $this->assertSame('recommended', $provider->normalize_sort('bogus'));
        $this->assertSame('Giá thấp đến cao', $provider->get_sort_label('price_asc'));
        $this->assertSame('Nên đặt', $provider->get_sort_label('bogus'));
    }

    public function test_sort_options_can_be_filtered()
    {
        add_filter('jankx/advanced_search/sort_options', function ($options) {
            $options['newest_first'] = 'Mới nhất nhất';

            return $options;
        });

        $provider = $this->provider();

        $this->assertArrayHasKey('newest_first', $provider->get_sort_options());
        $this->assertSame('newest_first', $provider->normalize_sort('newest_first'));
        $this->assertSame('Mới nhất nhất', $provider->get_sort_label('newest_first'));
    }

    public function test_post_type_config_is_derived_from_the_registries()
    {
        $provider = $this->provider();

        $tour = $provider->get_post_type_config('tour');
        $this->assertSame('Tour', $tour['label']);
        $this->assertSame('_jankx_price', $tour['price_meta']);
        $this->assertSame(['_tour_price'], $tour['legacy_price_metas']);
        $this->assertSame('jankx_rating_average', $tour['rating_meta']);
        $this->assertSame('jankx_rating_count', $tour['review_meta']);
        $this->assertSame(
            ['tour_category', 'destination', 'tour_duration', 'tour_tag'],
            $tour['tag_taxonomies']
        );

        // Not a product type → no price keys at all.
        $post = $provider->get_post_type_config('post');
        $this->assertSame('Cẩm nang', $post['label']);
        $this->assertSame('', $post['price_meta']);
        $this->assertSame([], $post['legacy_price_metas']);
    }

    public function test_unknown_post_type_config_is_fully_defaulted()
    {
        $config = $this->provider()->get_post_type_config('not-registered');

        foreach (['price_meta', 'price_from_meta', 'days_meta', 'nights_meta'] as $key) {
            $this->assertSame('', $config[$key], "{$key} must default to an empty string");
        }

        $this->assertSame('not-registered', $config['label']);
        $this->assertSame([], $config['legacy_price_metas']);
        $this->assertSame([], $config['tag_taxonomies']);

        // The rating repository is keyed by post ID, so it applies everywhere.
        $this->assertSame('jankx_rating_average', $config['rating_meta']);
        $this->assertSame('jankx_rating_count', $config['review_meta']);
    }

    public function test_post_type_config_filter_can_declare_extra_meta()
    {
        $this->declareTourSearchMeta();

        $config = $this->provider()->get_post_type_config('tour');

        $this->assertSame('_tour_price_is_from', $config['price_from_meta']);
        $this->assertSame('_tour_duration_days', $config['days_meta']);
        $this->assertSame('_tour_duration_nights', $config['nights_meta']);

        // Other post types are untouched.
        $this->assertSame('', $this->provider()->get_post_type_config('place')['days_meta']);
    }

    public function test_price_meta_keys_include_primary_and_legacy_keys()
    {
        $provider = $this->provider();

        $this->assertSame(['_jankx_price', '_tour_price'], $provider->price_meta_keys(['tour']));
        $this->assertSame(['_jankx_price', '_service_price'], $provider->price_meta_keys(['service']));

        // Several keys → SearchQuery sorts in PHP through the product registry.
        $this->assertSame(
            ['_jankx_price', '_tour_price', '_place_price', '_product_price'],
            $provider->price_meta_keys(['tour', 'place', 'product'])
        );

        // Post types without a product contribute nothing.
        $this->assertSame(['_jankx_price', '_tour_price'], $provider->price_meta_keys(['tour', 'post']));
        $this->assertSame([], $provider->price_meta_keys(['post']));
    }

    public function test_price_meta_keys_can_be_pinned_for_sql_sorting()
    {
        add_filter('jankx/advanced_search/price_meta_keys', function ($keys) {
            return ['_jankx_price'];
        });

        $this->assertSame(['_jankx_price'], $this->provider()->price_meta_keys(['tour', 'place']));
    }

    public function test_rating_meta_keys_use_the_rating_repository_for_every_type()
    {
        $provider = $this->provider();

        $this->assertSame(['jankx_rating_average'], $provider->rating_meta_keys(['tour']));
        $this->assertSame(['jankx_rating_average'], $provider->rating_meta_keys(['post']));
        $this->assertSame(['jankx_rating_average'], $provider->rating_meta_keys(['tour', 'place', 'post']));
    }

    public function test_rating_meta_keys_include_legacy_display_meta_when_declared()
    {
        add_filter('jankx/comment_rating/legacy_meta_map', function ($map) {
            $map['tour'] = '_tour_rating';
            $map['place'] = '_place_rating';

            return $map;
        });

        $this->assertSame(
            ['jankx_rating_average', '_tour_rating', '_place_rating'],
            $this->provider()->rating_meta_keys(['tour', 'place'])
        );
    }

    public function test_format_post_builds_card_data()
    {
        $id = $this->seedTour();
        $post = $this->seed([
            'post_type' => 'place',
            'post_title' => 'Tam Cốc Bích Động',
            'post_content' => 'Quần thể danh thắng.',
            'meta_input' => ['_place_price' => 150000],
            'terms_input' => ['place_type' => ['Danh thắng']],
        ]);

        $item = $this->provider()->format_post($post);

        $this->assertSame($id + 1, $item['id']);
        $this->assertSame('place', $item['post_type']);
        $this->assertSame('Địa điểm', $item['post_type_label']);
        $this->assertSame('Tam Cốc Bích Động', $item['title']);
        $this->assertSame('https://example.com/?p=' . ($id + 1), $item['permalink']);
        $this->assertSame('Danh thắng', $item['tag']);
        $this->assertSame(150000.0, $item['price']);
        $this->assertTrue($item['has_price']);
        $this->assertFalse($item['price_from']);
        $this->assertSame(0.0, $item['rating']);
        $this->assertSame('', $item['duration']);
    }

    public function test_format_post_tour_rating_and_duration()
    {
        $this->declareTourSearchMeta();

        $id = $this->seedTour();
        $item = $this->provider()->format_post(PostStore::get($id));

        $this->assertSame(4.8, $item['rating']);
        $this->assertSame(12, $item['review_count']);
        $this->assertSame(1500000.0, $item['price']);
        $this->assertTrue($item['price_from']);
        $this->assertSame('1 ngày', $item['duration']);
        $this->assertTrue($item['has_duration']);
        $this->assertSame('Tour phổ thông', $item['tag']);
    }

    public function test_price_resolves_the_legacy_key_through_the_product_registry()
    {
        $id = $this->seedTour();

        // New data on the canonical key wins over the legacy one.
        $this->assertSame(1500000.0, $this->provider()->get_price(PostStore::get($id)));

        PostStore::updateMeta($id, '_jankx_price', 2000000);
        $this->assertSame(2000000.0, $this->provider()->get_price(PostStore::get($id)));
    }

    public function test_sale_price_wins_for_every_product_type()
    {
        $product = $this->seed([
            'post_type' => 'product',
            'post_title' => 'Cốm Cháy Cố Đô',
            'meta_input' => [
                '_product_price' => 1000000,
                '_product_sale_price' => 800000,
            ],
        ]);

        $provider = $this->provider();

        $this->assertSame(800000.0, $provider->get_price(PostStore::get($product)));

        // Without a sale price the regular price is used.
        PostStore::updateMeta($product, '_product_sale_price', 0);
        $this->assertSame(1000000.0, $provider->get_price(PostStore::get($product)));
    }

    public function test_tour_sale_price_is_applied_without_any_extra_configuration()
    {
        $id = $this->seedTour(['meta_input' => ['_tour_price' => 1500000, '_tour_sale_price' => 1200000]]);

        $this->assertSame(1200000.0, $this->provider()->get_price(PostStore::get($id)));
    }

    public function test_post_without_a_product_reports_no_price()
    {
        $id = $this->seed(['post_type' => 'post', 'post_title' => 'Bài viết', 'meta_input' => []]);

        $this->assertSame(0.0, $this->provider()->get_price(PostStore::get($id)));
    }

    public function test_tag_falls_back_to_post_type_label()
    {
        $post = $this->seed([
            'post_type' => 'post',
            'post_title' => 'Cẩm nang du lịch Ninh Bình',
            'terms_input' => [],
        ]);

        $this->assertSame('Cẩm nang', $this->provider()->get_tag($post));
    }

    public function test_generic_taxonomy_is_only_used_when_no_custom_one_matches()
    {
        $id = $this->seed([
            'post_type' => 'post',
            'post_title' => 'Bài viết có chuyên mục',
            'terms_input' => ['category' => ['Ninh Bình']],
        ]);

        $this->assertSame('Ninh Bình', $this->provider()->get_tag(PostStore::get($id)));
    }

    public function test_missing_post_does_not_claim_to_be_any_post_type()
    {
        $item = $this->provider()->format_post(9999);

        $this->assertSame('', $item['post_type']);
        $this->assertSame(0, $item['id']);
        $this->assertSame(0.0, $item['price']);
        $this->assertSame('', $item['tag']);
    }

    public function test_format_price_and_duration()
    {
        $provider = $this->provider();

        $this->assertSame('1.000.000đ', $provider->format_price(1000000));
        $this->assertSame('89.500đ', $provider->format_price(89500));
        $this->assertSame('', $provider->format_price(0));

        $this->assertSame('4 ngày 3 đêm', $provider->format_duration(4, 3));
        $this->assertSame('2 ngày', $provider->format_duration(2, 0));
        $this->assertSame('1 đêm', $provider->format_duration(0, 1));
        $this->assertSame('', $provider->format_duration(0, 0));
    }

    public function test_price_and_duration_formatting_can_be_filtered()
    {
        add_filter('jankx/advanced_search/format_price', function ($formatted, $price) {
            return $formatted . ' VND';
        }, 10, 2);

        add_filter('jankx/advanced_search/duration_labels', function ($labels) {
            $labels['day'] = 'd';
            $labels['night'] = 'n';

            return $labels;
        });

        $provider = $this->provider();

        $this->assertSame('1.000.000đ VND', $provider->format_price(1000000));
        $this->assertSame('2 d 1 n', $provider->format_duration(2, 1));
    }
}