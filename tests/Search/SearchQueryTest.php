<?php

namespace Jankx\Extensions\AdvancedSearch\Tests\Search;

use Jankx\Extensions\AdvancedSearch\Search\SearchProvider;
use Jankx\Extensions\AdvancedSearch\Search\SearchQuery;
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
                '_tour_rating' => 4.8,
                '_tour_review_count' => 12,
                '_tour_duration_days' => 1,
                '_tour_duration_nights' => 0,
            ],
        ]);
        $tourB = $this->seedTour([
            'post_title' => 'Tour Hoa Lư Cổ Đô cao cấp',
            'post_date' => '2026-06-01 00:00:00',
            'meta_input' => [
                '_tour_price' => 3500000,
                '_tour_rating' => 4.9,
                '_tour_review_count' => 30,
                '_tour_duration_days' => 2,
                '_tour_duration_nights' => 1,
            ],
        ]);
        $exp = $this->seed([
            'post_type' => 'experience',
            'post_title' => 'Chèo thuyền Tràng An',
            'post_date' => '2026-07-01 00:00:00',
            'meta_input' => [
                '_experience_price' => 500000,
                '_experience_rating' => 4.5,
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

        return compact('tourA', 'tourB', 'exp', 'place', 'product', 'guide');
    }

    public function test_keyword_filters_results()
    {
        $ids = $this->seedCatalog();

        $result = $this->query()->run('Tràng An', SearchProvider::TAB_ALL, SearchProvider::SORT_RECOMMENDED, 1, 12);

        $this->assertSame(['Chèo thuyền Tràng An', 'Tour Tràng An 1 ngày'], $this->titles($result));
        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['total_pages']);
    }

    public function test_tab_scopes_to_post_types()
    {
        $this->seedCatalog();

        $result = $this->query()->run('', SearchProvider::TAB_EXPERIENCE, SearchProvider::SORT_RECOMMENDED, 1, 12);
        $this->assertSame(['Chèo thuyền Tràng An'], $this->titles($result));

        $result = $this->query()->run('', SearchProvider::TAB_PLACE, SearchProvider::SORT_RECOMMENDED, 1, 12);
        $this->assertSame(['Tam Cốc Bích Động'], $this->titles($result));

        $result = $this->query()->run('', SearchProvider::TAB_GUIDE, SearchProvider::SORT_RECOMMENDED, 1, 12);
        $this->assertSame(['Cẩm nang du lịch Ninh Bình'], $this->titles($result));

        // Tour & Dịch vụ covers tours + products (newest first).
        $result = $this->query()->run('', SearchProvider::TAB_TOUR, SearchProvider::SORT_RECOMMENDED, 1, 12);
        $this->assertSame(
            ['Cốm Cháy Cố Đô', 'Tour Hoa Lư Cổ Đô cao cấp', 'Tour Tràng An 1 ngày'],
            $this->titles($result)
        );
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

    public function test_price_asc_single_type_uses_sql_sort()
    {
        $this->seedCatalog();

        $result = $this->query()->run('', SearchProvider::TAB_TOUR, SearchProvider::SORT_PRICE_ASC, 1, 12);

        $this->assertSame(['Tour Tràng An 1 ngày', 'Tour Hoa Lư Cổ Đô cao cấp'], $this->titles($result));
        $this->assertSame(1500000.0, $result['items'][0]['price']);
        $this->assertSame(3500000.0, $result['items'][1]['price']);
    }

    public function test_price_desc_single_type_uses_sql_sort()
    {
        $this->seedCatalog();

        $result = $this->query()->run('', SearchProvider::TAB_TOUR, SearchProvider::SORT_PRICE_DESC, 1, 12);

        $this->assertSame(['Tour Hoa Lư Cổ Đô cao cấp', 'Tour Tràng An 1 ngày'], $this->titles($result));
    }

    public function test_price_sort_mixed_types_uses_php_sort()
    {
        $this->seedCatalog();

        // Mixed post types → different price meta keys → PHP-side sort.
        // The guide has no price meta (0) so it sorts first ascending.
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

    public function test_rating_sort_mixed_types_uses_php_sort()
    {
        $this->seedCatalog();

        $result = $this->query()->run('', SearchProvider::TAB_ALL, SearchProvider::SORT_RATING, 1, 12);

        // Products and guides have no rating (0) → they come last.
        $this->assertSame(
            ['Tour Hoa Lư Cổ Đô cao cấp', 'Tour Tràng An 1 ngày', 'Chèo thuyền Tràng An'],
            array_slice($this->titles($result), 0, 3)
        );
    }

    public function test_pagination_slices_and_clamps_page()
    {
        $ids = $this->seedCatalog();
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

        $result = $this->query()->run('', SearchProvider::TAB_TOUR, SearchProvider::SORT_RECOMMENDED, 1, 12);

        $this->assertCount(2, $result['items']);
    }
}
