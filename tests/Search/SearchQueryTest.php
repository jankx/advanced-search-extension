<?php

namespace Jankx\Extensions\AdvancedSearch\Tests\Search;

use Jankx\Extensions\AdvancedSearch\Search\SearchProvider;
use Jankx\Extensions\AdvancedSearch\Tests\Support\ServiceProductStub;
use Jankx\Extensions\AdvancedSearch\Tests\Support\SiteRegistry;
use Jankx\Extensions\AdvancedSearch\Tests\TestCase;

/**
 * @coversDefaultClass \Jankx\Extensions\AdvancedSearch\Search\SearchQuery
 */
class SearchQueryTest extends TestCase
{
    protected function seedCatalog()
    {
        $tourA = $this->seedTour([
            'post_title' => 'Tour Tràng An 1 ngày',
            'post_date' => '2026-05-01 00:00:00',
            'meta_input' => [
                '_tour_price' => 1500000,
                '_tour_price_is_from' => 1,
                'jankx_rating_average' => 4.8,
                'jankx_rating_count' => 12,
                '_tour_duration_days' => 1,
                '_tour_duration_nights' => 0,
            ],
        ]);
        $tourB = $this->seedTour([
            'post_title' => 'Tour Hoa Lư Cổ Đô cao cấp',
            'post_date' => '2026-06-01 00:00:00',
            'post_content' => 'Khám phá Hoa Lư và Cố Đô bằng xe điện.',
            'meta_input' => [
                '_tour_price' => 3500000,
                'jankx_rating_average' => 4.9,
                'jankx_rating_count' => 30,
                '_tour_duration_days' => 2,
                '_tour_duration_nights' => 1,
            ],
        ]);
        $tourC = $this->seedTour([
            'post_title' => 'Chèo thuyền Tràng An',
            'post_date' => '2026-07-01 00:00:00',
            'meta_input' => [
                '_tour_price' => 500000,
                'jankx_rating_average' => 4.5,
            ],
        ]);
        $place = $this->seed([
            'post_type' => 'place',
            'post_title' => 'Tam Cốc Bích Động',
            'post_date' => '2026-03-01 00:00:00',
            'meta_input' => [
                '_place_price' => 150000,
            ],
        ]);
        $product = $this->seed([
            'post_type' => 'product',
            'post_title' => 'Cốm Cháy Cố Đô',
            'post_date' => '2026-08-01 00:00:00',
            'meta_input' => [
                '_product_price' => 1000000,
            ],
        ]);
        $guide = $this->seed([
            'post_type' => 'post',
            'post_title' => 'Cẩm nang du lịch Ninh Bình',
            'post_date' => '2026-02-01 00:00:00',
        ]);

        return compact('tourA', 'tourB', 'tourC', 'place', 'product', 'guide');
    }

    public function test_keyword_filters_results()
    {
        $this->seedCatalog();

        $result = $this->query()->run('Tràng An', SearchProvider::TAB_ALL, SearchProvider::SORT_RECOMMENDED, 1, 12);

        $this->assertSame(['Chèo thuyền Tràng An', 'Tour Tràng An 1 ngày'], $this->titles($result));
        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['total_pages']);
    }

    public function test_tab_scopes_to_post_types()
    {
        $this->seedCatalog();

        $result = $this->query()->run('', 'place', SearchProvider::SORT_RECOMMENDED, 1, 12);
        $this->assertSame(['Tam Cốc Bích Động'], $this->titles($result));

        $result = $this->query()->run('', 'guide', SearchProvider::SORT_RECOMMENDED, 1, 12);
        $this->assertSame(['Cẩm nang du lịch Ninh Bình'], $this->titles($result));

        // The product tab covers every purchasable post type, newest first.
        $result = $this->query()->run('', 'tour', SearchProvider::SORT_RECOMMENDED, 1, 12);
        $this->assertSame(
            [
                'Cốm Cháy Cố Đô',
                'Chèo thuyền Tràng An',
                'Tour Hoa Lư Cổ Đô cao cấp',
                'Tour Tràng An 1 ngày',
                'Tam Cốc Bích Động',
            ],
            $this->titles($result)
        );
    }

    /**
     * A post type registered as a product after boot is searchable immediately,
     * with no change to the provider or the query.
     */
    public function test_a_newly_registered_product_post_type_is_queried()
    {
        SiteRegistry::registerProduct('cruise', ServiceProductStub::class, ['cruise_region'], 'Cruise');
        $this->seed([
            'post_type' => 'cruise',
            'post_title' => 'Du thuyền Hạ Long 2 ngày',
            'post_date' => '2026-04-01 00:00:00',
            'meta_input' => ['_service_price' => 5000000],
        ]);

        $result = $this->query()->run('Hạ Long', 'tour', SearchProvider::SORT_RECOMMENDED, 1, 12);

        $this->assertSame(['Du thuyền Hạ Long 2 ngày'], $this->titles($result));
        $this->assertSame(5000000.0, $result['items'][0]['price']);
        $this->assertSame('Cruise', $result['items'][0]['post_type_label']);
    }

    public function test_date_sort_is_newest_first()
    {
        $this->seedCatalog();

        $result = $this->query()->run('', SearchProvider::TAB_ALL, SearchProvider::SORT_DATE, 1, 12);

        $this->assertSame(
            ['Cốm Cháy Cố Đô', 'Chèo thuyền Tràng An', 'Tour Hoa Lư Cổ Đô cao cấp', 'Tour Tràng An 1 ngày', 'Tam Cốc Bích Động', 'Cẩm nang du lịch Ninh Bình'],
            $this->titles($result)
        );
    }

    public function test_recommended_without_keyword_uses_date_order()
    {
        $this->seedCatalog();

        $result = $this->query()->run('', SearchProvider::TAB_ALL, SearchProvider::SORT_RECOMMENDED, 1, 12);

        $this->assertSame(
            ['Cốm Cháy Cố Đô', 'Chèo thuyền Tràng An', 'Tour Hoa Lư Cổ Đô cao cấp', 'Tour Tràng An 1 ngày', 'Tam Cốc Bích Động', 'Cẩm nang du lịch Ninh Bình'],
            $this->titles($result)
        );
    }

    /**
     * Legacy content keeps several price meta keys alive, so the sort runs in
     * PHP through the product registry. Pinning one key restores SQL sorting.
     */
    public function test_price_asc_resolves_legacy_meta_keys_in_php()
    {
        $this->seedCatalog();
        $this->seed([
            'post_type' => 'place',
            'post_title' => 'Địa điểm cao cấp',
            'post_date' => '2026-01-02 00:00:00',
            'meta_input' => ['_place_price' => 2000000],
        ]);
        $this->seed([
            'post_type' => 'place',
            'post_title' => 'Địa điểm giá rẻ',
            'post_date' => '2026-01-01 00:00:00',
            'meta_input' => ['_place_price' => 300000],
        ]);

        $result = $this->query()->run('', 'place', SearchProvider::SORT_PRICE_ASC, 1, 12);

        $this->assertSame(
            ['Tam Cốc Bích Động', 'Địa điểm giá rẻ', 'Địa điểm cao cấp'],
            $this->titles($result)
        );
        $this->assertSame(150000.0, $result['items'][0]['price']);
        $this->assertSame(300000.0, $result['items'][1]['price']);
        $this->assertSame(2000000.0, $result['items'][2]['price']);
    }

    public function test_price_desc_resolves_legacy_meta_keys_in_php()
    {
        $this->seedCatalog();
        $this->seed([
            'post_type' => 'place',
            'post_title' => 'Địa điểm cao cấp',
            'post_date' => '2026-01-02 00:00:00',
            'meta_input' => ['_place_price' => 2000000],
        ]);
        $this->seed([
            'post_type' => 'place',
            'post_title' => 'Địa điểm giá rẻ',
            'post_date' => '2026-01-01 00:00:00',
            'meta_input' => ['_place_price' => 300000],
        ]);

        $result = $this->query()->run('', 'place', SearchProvider::SORT_PRICE_DESC, 1, 12);

        $this->assertSame(
            ['Địa điểm cao cấp', 'Địa điểm giá rẻ', 'Tam Cốc Bích Động'],
            $this->titles($result)
        );
    }

    public function test_price_sort_uses_sql_when_only_one_meta_key_applies()
    {
        add_filter('jankx/advanced_search/price_meta_keys', function () {
            return ['_place_price'];
        });

        $this->seed([
            'post_type' => 'place',
            'post_title' => 'Địa điểm giá rẻ',
            'post_date' => '2026-01-01 00:00:00',
            'meta_input' => ['_place_price' => 300000],
        ]);
        $this->seed([
            'post_type' => 'place',
            'post_title' => 'Địa điểm cao cấp',
            'post_date' => '2026-01-02 00:00:00',
            'meta_input' => ['_place_price' => 2000000],
        ]);

        // SQL sorting on the pinned key ignores the posts without it.
        $result = $this->query()->run('', 'place', SearchProvider::SORT_PRICE_ASC, 1, 12);

        $this->assertSame(['Địa điểm giá rẻ', 'Địa điểm cao cấp'], $this->titles($result));
    }

    public function test_price_sort_mixed_types_uses_php_sort()
    {
        $this->seedCatalog();

        // Mixed post types → several price meta keys → PHP-side sort.
        // The guide has no product (0) so it sorts first ascending.
        $result = $this->query()->run('', SearchProvider::TAB_ALL, SearchProvider::SORT_PRICE_ASC, 1, 12);

        $this->assertSame(
            ['Cẩm nang du lịch Ninh Bình', 'Tam Cốc Bích Động', 'Chèo thuyền Tràng An', 'Cốm Cháy Cố Đô', 'Tour Tràng An 1 ngày', 'Tour Hoa Lư Cổ Đô cao cấp'],
            $this->titles($result)
        );
        $this->assertSame(0.0, $result['items'][0]['price']);
        $this->assertSame(150000.0, $result['items'][1]['price']);
        $this->assertSame(3500000.0, $result['items'][5]['price']);
    }

    public function test_price_desc_mixed_types_uses_php_sort()
    {
        $this->seedCatalog();

        $result = $this->query()->run('', SearchProvider::TAB_ALL, SearchProvider::SORT_PRICE_DESC, 1, 12);

        $this->assertSame(
            ['Tour Hoa Lư Cổ Đô cao cấp', 'Tour Tràng An 1 ngày', 'Cốm Cháy Cố Đô', 'Chèo thuyền Tràng An', 'Tam Cốc Bích Động', 'Cẩm nang du lịch Ninh Bình'],
            $this->titles($result)
        );
    }

    public function test_rating_sort_reads_the_rating_repository()
    {
        $this->seedCatalog();

        $result = $this->query()->run('', SearchProvider::TAB_ALL, SearchProvider::SORT_RATING, 1, 12);

        // Unrated posts score 0 and come last, in insertion order.
        $this->assertSame(
            ['Tour Hoa Lư Cổ Đô cao cấp', 'Tour Tràng An 1 ngày', 'Chèo thuyền Tràng An'],
            array_slice($this->titles($result), 0, 3)
        );
        $this->assertSame(4.9, $result['items'][0]['rating']);
        $this->assertSame(30, $result['items'][0]['review_count']);
    }

    public function test_pagination_slices_and_clamps_page()
    {
        $this->seedCatalog();
        // 6 posts, 2 per page → 3 pages.
        $result = $this->query()->run('', SearchProvider::TAB_ALL, SearchProvider::SORT_DATE, 1, 2);

        $this->assertSame(6, $result['total']);
        $this->assertSame(3, $result['total_pages']);
        $this->assertSame(1, $result['page']);
        $this->assertSame(['Cốm Cháy Cố Đô', 'Chèo thuyền Tràng An'], $this->titles($result));

        $result = $this->query()->run('', SearchProvider::TAB_ALL, SearchProvider::SORT_DATE, 3, 2);
        $this->assertSame(['Tam Cốc Bích Động', 'Cẩm nang du lịch Ninh Bình'], $this->titles($result));

        // A page beyond the last one clamps to the final page.
        $result = $this->query()->run('', SearchProvider::TAB_ALL, SearchProvider::SORT_DATE, 99, 2);
        $this->assertSame(3, $result['page']);
    }

    public function test_empty_keyword_with_no_matches()
    {
        $result = $this->query()->run('không tồn tại', SearchProvider::TAB_ALL, SearchProvider::SORT_RECOMMENDED, 1, 12);

        $this->assertSame([], $result['items']);
        $this->assertSame(0, $result['total']);
        $this->assertSame(0, $result['total_pages']);
    }

    public function test_search_respects_publish_status()
    {
        $this->seedCatalog();
        $this->seed([
            'post_type' => 'tour',
            'post_status' => 'draft',
            'post_title' => 'Tour nháp bí mật',
            'meta_input' => ['_tour_price' => 999000],
        ]);

        // The product tab covers tour, place, service and product.
        $result = $this->query()->run('', 'tour', SearchProvider::SORT_RECOMMENDED, 1, 12);

        $this->assertCount(5, $result['items']);
        $this->assertNotContains('Tour nháp bí mật', $this->titles($result));
    }

    /**
     * A tab that resolves to nothing falls back to every discovered post type
     * rather than guessing a single one.
     */
    public function test_tab_without_post_types_falls_back_to_discovered_types()
    {
        add_filter('jankx/advanced_search/tabs', function ($tabs) {
            $tabs['everything'] = [
                'label' => 'Tất cả mọi thứ',
                'post_types' => [],
            ];

            return $tabs;
        });

        $this->seedCatalog();

        $result = $this->query()->run('', 'everything', SearchProvider::SORT_DATE, 1, 12);

        $this->assertSame(6, $result['total']);
    }
}