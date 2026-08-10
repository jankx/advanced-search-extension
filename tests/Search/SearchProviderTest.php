<?php

namespace Jankx\Extensions\AdvancedSearch\Tests\Search;

use Jankx\Extensions\AdvancedSearch\Search\SearchProvider;
use Jankx\Extensions\AdvancedSearch\Tests\TestCase;

/**
 * @coversDefaultClass \Jankx\Extensions\AdvancedSearch\Search\SearchProvider
 */
class SearchProviderTest extends TestCase
{
    public function test_tabs_cover_all_post_types()
    {
        $tabs = $this->provider()->get_tabs();

        $expected = [
            SearchProvider::TAB_ALL,
            SearchProvider::TAB_EXPERIENCE,
            SearchProvider::TAB_GUIDE,
            SearchProvider::TAB_PLACE,
            SearchProvider::TAB_TOUR,
        ];

        $this->assertSame($expected, array_keys($tabs));
        $this->assertSame('Tất cả', $tabs[SearchProvider::TAB_ALL]['label']);
        $this->assertSame('Trải nghiệm', $tabs[SearchProvider::TAB_EXPERIENCE]['label']);
        $this->assertSame('Cẩm nang du lịch', $tabs[SearchProvider::TAB_GUIDE]['label']);
        $this->assertSame('Ăn gì ở đâu', $tabs[SearchProvider::TAB_PLACE]['label']);
        $this->assertSame('Tour & Dịch vụ', $tabs[SearchProvider::TAB_TOUR]['label']);
    }

    public function test_tour_tab_covers_tour_and_product()
    {
        $this->assertSame(
            ['tour', 'product'],
            $this->provider()->get_tab_post_types(SearchProvider::TAB_TOUR)
        );
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

    public function test_price_meta_keys_group_by_unique_key()
    {
        $provider = $this->provider();

        // Single type shares one key → SQL sort is safe.
        $this->assertSame(['_tour_price'], $provider->price_meta_keys(['tour']));
        $this->assertSame(['_product_price'], $provider->price_meta_keys(['product']));

        // Mixed types with different keys → forced PHP sort.
        $this->assertSame(
            ['_tour_price', '_experience_price', '_place_price', '_product_price'],
            $provider->price_meta_keys(['tour', 'experience', 'place', 'product'])
        );

        // Types without a price meta key are excluded.
        $this->assertSame(['_tour_price'], $provider->price_meta_keys(['tour', 'post']));
        $this->assertSame([], $provider->price_meta_keys(['post']));
    }

    public function test_rating_meta_keys_group_by_unique_key()
    {
        $provider = $this->provider();

        $this->assertSame(['_tour_rating'], $provider->rating_meta_keys(['tour']));
        $this->assertSame([], $provider->rating_meta_keys(['post', 'product']));
        $this->assertSame(
            ['_tour_rating', '_experience_rating', '_place_rating'],
            $provider->rating_meta_keys(['tour', 'experience', 'place'])
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
        $id = $this->seedTour();
        $item = $this->provider()->format_post(\Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::get($id));

        $this->assertSame(4.8, $item['rating']);
        $this->assertSame(12, $item['review_count']);
        $this->assertSame(1500000.0, $item['price']);
        $this->assertTrue($item['price_from']);
        $this->assertSame('1 ngày', $item['duration']);
        $this->assertTrue($item['has_duration']);
        $this->assertSame('Tour phổ thông', $item['tag']);
    }

    public function test_product_price_uses_sale_price_when_set()
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
        $post = \Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::get($product);

        $this->assertSame(800000.0, $provider->get_price($post));

        // Without a sale price the regular price is used.
        \Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::updateMeta($product, '_product_sale_price', 0);
        $this->assertSame(1000000.0, $provider->get_price($post));
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
}
